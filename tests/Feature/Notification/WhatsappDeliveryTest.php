<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Notification\Contracts\WhatsAppGatewayInterface;
use App\Modules\Notification\Jobs\SendWhatsAppMessage;
use App\Modules\Notification\Models\WhatsappMessage;
use App\Modules\Notification\Support\GatewayResult;
use App\Modules\Notification\Support\OutboundMessage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

/**
 * The two weaknesses that lost the `order.assigned` pair on 2026-10-17 and
 * then kept the reason hidden for days:
 *
 *  - the retry budget was two and a half minutes, so an outage longer than
 *    that **destroyed** a message instead of delaying it;
 *  - `whatsapp_messages.response` recorded why, and no screen showed it.
 *
 * Both are asserted here rather than left to a code comment, because both are
 * invisible until the next outage.
 */
final class WhatsappDeliveryTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = $this->userWithRole(RoleEnum::Admin, ['email' => 'owner@example.test']);
    }

    private function message(array $attributes = []): WhatsappMessage
    {
        return WhatsappMessage::query()->create([
            'to' => '919604910320',
            'template' => 'order.assigned',
            'body' => 'Your tanker is on its way.',
            'status' => WhatsappMessage::STATUS_PENDING,
            ...$attributes,
        ]);
    }

    /**
     * Swap the bound gateway for one that always answers the same way.
     */
    private function fakeGateway(bool $delivered, string $response): void
    {
        $this->app->bind(WhatsAppGatewayInterface::class, fn (): WhatsAppGatewayInterface => new class($delivered, $response) implements WhatsAppGatewayInterface
        {
            public function __construct(
                private readonly bool $delivered,
                private readonly string $response,
            ) {}

            public function send(OutboundMessage $message): GatewayResult
            {
                return $this->delivered
                    ? GatewayResult::sent($this->response)
                    : GatewayResult::failed($this->response);
            }
        });
    }

    private function runJob(WhatsappMessage $record, int $attempt): void
    {
        $job = new SendWhatsAppMessage($record->id);

        $queueJob = new FakeJob;
        $queueJob->attempts = $attempt;
        $job->setJob($queueJob);

        try {
            $job->handle($this->app->make(WhatsAppGatewayInterface::class));
        } catch (RuntimeException) {
            // The job rethrows so the queue retries; the row is what matters.
        }
    }

    // -----------------------------------------------------------------------
    // The retry budget
    // -----------------------------------------------------------------------

    public function test_the_retry_budget_is_about_fifty_minutes_not_two(): void
    {
        $job = new SendWhatsAppMessage(1);

        self::assertSame(6, $job->tries);
        self::assertSame([30, 120, 300, 900, 1800], $job->backoff);

        // The number that actually matters: how long an outage a message can
        // survive. The old three tries gave 150 seconds.
        self::assertGreaterThan(2400, array_sum($job->backoff));
    }

    public function test_a_refused_send_stays_pending_while_tries_remain(): void
    {
        $this->fakeGateway(false, 'WhatsApp is not connected. Scan QR first.');
        $record = $this->message();

        $this->runJob($record, attempt: 1);

        $record->refresh();

        // Pending, not failed: the queue is still going to retry for the best
        // part of an hour, and the log must not call the message dead.
        self::assertSame(WhatsappMessage::STATUS_PENDING, $record->status);
        self::assertSame(1, $record->attempts);
        self::assertSame('WhatsApp is not connected. Scan QR first.', $record->response);
    }

    public function test_the_last_attempt_is_the_one_that_marks_it_failed(): void
    {
        $this->fakeGateway(false, 'WhatsApp is not connected. Scan QR first.');
        $record = $this->message(['attempts' => 5]);

        $this->runJob($record, attempt: 6);

        $record->refresh();

        self::assertSame(WhatsappMessage::STATUS_FAILED, $record->status);
        self::assertSame(6, $record->attempts);
    }

    public function test_a_delivered_send_is_recorded_as_sent(): void
    {
        $this->fakeGateway(true, 'queued');
        $record = $this->message();

        $this->runJob($record, attempt: 1);

        $record->refresh();

        self::assertSame(WhatsappMessage::STATUS_SENT, $record->status);
        self::assertNotNull($record->sent_at);
    }

    // -----------------------------------------------------------------------
    // The log finally says why
    // -----------------------------------------------------------------------

    public function test_the_log_shows_why_a_message_failed(): void
    {
        $this->message([
            'status' => WhatsappMessage::STATUS_FAILED,
            'attempts' => 6,
            'response' => 'HTTP 409: WhatsApp is not connected. Scan QR first.',
        ]);

        $row = $this->actingAs($this->admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.whatsapp.index').'?draw=1&start=0&length=10')
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->json('data.0');

        // Asserted on the rendered **column**, not on the payload: yajra
        // serialises the model's own `response` attribute either way, so a
        // bare assertSee would have passed against the old code that displayed
        // nothing. The rendered column is what the owner actually reads.
        self::assertStringContainsString('WhatsApp is not connected. Scan QR first.', $row['reason']);
        self::assertStringContainsString('Resend', $row['action']);
    }

    public function test_a_delivered_message_shows_neither_a_reason_nor_a_resend_button(): void
    {
        $this->message([
            'status' => WhatsappMessage::STATUS_SENT,
            'attempts' => 1,
            'response' => 'queued',
            'sent_at' => now(),
        ]);

        $row = $this->actingAs($this->admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.whatsapp.index').'?draw=1&start=0&length=10')
            ->assertOk()
            ->json('data.0');

        // A delivered row's response is noise, and resending would deliver the
        // same notice to the customer twice.
        self::assertStringNotContainsString('queued', $row['reason']);
        self::assertSame('', $row['action']);
    }

    // -----------------------------------------------------------------------
    // Resend
    // -----------------------------------------------------------------------

    public function test_resend_puts_a_failed_message_back_on_the_queue(): void
    {
        Queue::fake();

        $record = $this->message([
            'status' => WhatsappMessage::STATUS_FAILED,
            'attempts' => 6,
            'response' => 'HTTP 409: not connected',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.whatsapp.resend', $record))
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(SendWhatsAppMessage::class);

        $record->refresh();

        self::assertSame(WhatsappMessage::STATUS_PENDING, $record->status);
        // History is kept: "6 tries" is what tells the owner this one has been
        // fighting, so the counter does not start again at zero.
        self::assertSame(6, $record->attempts);
    }

    public function test_a_message_stuck_pending_can_also_be_resent(): void
    {
        Queue::fake();

        // No queue worker was running, so nothing ever attempted it.
        $record = $this->message(['attempts' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.whatsapp.resend', $record))
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(SendWhatsAppMessage::class);
    }

    public function test_an_already_delivered_message_is_refused(): void
    {
        Queue::fake();

        $record = $this->message([
            'status' => WhatsappMessage::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.whatsapp.resend', $record))
            ->assertRedirect()
            ->assertSessionHas('error');

        Queue::assertNothingPushed();

        self::assertSame(WhatsappMessage::STATUS_SENT, $record->refresh()->status);
    }

    public function test_a_driver_cannot_resend_a_message(): void
    {
        Queue::fake();

        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9766697520']);
        $record = $this->message(['status' => WhatsappMessage::STATUS_FAILED]);

        $this->actingAs($driver)
            ->post(route('admin.whatsapp.resend', $record))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }
}
