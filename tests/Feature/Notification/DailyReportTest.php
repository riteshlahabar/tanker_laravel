<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Notification\Contracts\WhatsAppGatewayInterface;
use App\Modules\Notification\Gateways\LogGateway;
use App\Modules\Notification\Gateways\NodeApiGateway;
use App\Modules\Notification\Models\WhatsappMessage;
use App\Modules\Notification\Services\DailyReportNotifier;
use App\Modules\Order\Models\Order;
use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class DailyReportTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private const ADMIN_NUMBER = '919812345678';

    private User $adminUser;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(SettingRepositoryInterface::class)->put('admin_whatsapp_number', self::ADMIN_NUMBER);

        $this->adminUser = $this->admin();
        $this->customer = $this->userWithRole(RoleEnum::Customer, [
            'first_name' => 'Sunita', 'last_name' => '', 'mobile' => '9333333333',
        ]);
    }

    public function test_the_report_lists_each_driver_with_their_delivery_counts(): void
    {
        $ramesh = $this->driver('Ramesh', '9111111111', 15000);
        $suresh = $this->driver('Suresh', '9222222222', 20000);

        $this->delivered($ramesh, 3);
        $this->delivered($suresh, 1);

        $body = app(DailyReportNotifier::class)->sendFor(now()->toDateString())['body'];

        $this->assertStringContainsString('Ramesh: 3 deliveries, 3 tankers', $body);
        $this->assertStringContainsString('Suresh: 1 delivery, 1 tanker', $body);
        $this->assertStringContainsString('Total deliveries: 4', $body);
        $this->assertStringContainsString('Tankers delivered: 4', $body);
    }

    public function test_drivers_who_delivered_nothing_are_left_out(): void
    {
        $ramesh = $this->driver('Ramesh', '9111111111', 15000);
        $this->driver('Idle', '9222222222', 20000);

        $this->delivered($ramesh, 1);

        $body = app(DailyReportNotifier::class)->sendFor(now()->toDateString())['body'];

        $this->assertStringContainsString('Ramesh', $body);
        $this->assertStringNotContainsString('Idle', $body);
    }

    public function test_a_day_with_no_deliveries_still_sends_a_report(): void
    {
        $this->driver('Ramesh', '9111111111', 15000);

        $result = app(DailyReportNotifier::class)->sendFor(now()->toDateString());

        $this->assertSame(1, $result['queued']);
        $this->assertStringContainsString('No deliveries today.', $result['body']);
        $this->assertStringContainsString('Total deliveries: 0', $result['body']);
    }

    public function test_the_report_only_counts_the_day_asked_for(): void
    {
        $ramesh = $this->driver('Ramesh', '9111111111', 15000);

        $this->delivered($ramesh, 2);
        $this->delivered($ramesh, 5, now()->subDays(3));

        $today = app(DailyReportNotifier::class)->sendFor(now()->toDateString())['body'];
        $this->assertStringContainsString('Total deliveries: 2', $today);

        $thatDay = app(DailyReportNotifier::class)
            ->sendFor(now()->subDays(3)->toDateString())['body'];
        $this->assertStringContainsString('Total deliveries: 5', $thatDay);
    }

    public function test_the_command_queues_the_report_to_the_owner(): void
    {
        $ramesh = $this->driver('Ramesh', '9111111111', 15000);
        $this->delivered($ramesh, 2);

        $this->artisan('tanker:daily-report')
            ->expectsOutputToContain('queued to '.self::ADMIN_NUMBER)
            ->assertSuccessful();

        $message = WhatsappMessage::query()->where('template', 'report.daily')->firstOrFail();

        $this->assertSame(self::ADMIN_NUMBER, $message->to);
        $this->assertStringContainsString('Ramesh: 2 deliveries, 2 tankers', (string) $message->body);
    }

    public function test_the_command_fails_loudly_when_no_owner_number_is_set(): void
    {
        app(SettingRepositoryInterface::class)->put('admin_whatsapp_number', '');

        $this->artisan('tanker:daily-report')
            ->expectsOutputToContain('No admin WhatsApp number is set')
            ->assertFailed();

        $this->assertSame(0, WhatsappMessage::query()->count());
    }

    public function test_the_command_accepts_a_date(): void
    {
        $ramesh = $this->driver('Ramesh', '9111111111', 15000);
        $this->delivered($ramesh, 4, now()->subDays(2));

        $this->artisan('tanker:daily-report', ['--date' => now()->subDays(2)->toDateString()])
            ->assertSuccessful();

        $this->assertStringContainsString(
            'Total deliveries: 4',
            (string) WhatsappMessage::query()->latest('id')->firstOrFail()->body,
        );
    }

    public function test_the_send_now_button_queues_the_same_report(): void
    {
        $ramesh = $this->driver('Ramesh', '9111111111', 15000);
        $this->delivered($ramesh, 2);

        $this->actingAs($this->adminUser)
            ->post(route('admin.whatsapp.daily-report'), ['date' => now()->toDateString()])
            ->assertSessionHas('success');

        $this->assertStringContainsString(
            'Ramesh: 2 deliveries',
            (string) WhatsappMessage::query()->where('template', 'report.daily')->firstOrFail()->body,
        );
    }

    public function test_a_driver_cannot_trigger_the_report_or_read_the_log(): void
    {
        $driver = $this->driver('Ramesh', '9111111111', 15000);

        $this->actingAs($driver)->get(route('admin.whatsapp.index'))->assertForbidden();
        $this->actingAs($driver)->post(route('admin.whatsapp.daily-report'))->assertForbidden();
    }

    public function test_the_log_driver_is_bound_by_default(): void
    {
        $this->assertInstanceOf(LogGateway::class, app(WhatsAppGatewayInterface::class));
    }

    public function test_setting_the_node_driver_swaps_the_gateway_with_no_code_change(): void
    {
        config(['services.whatsapp.driver' => 'node', 'services.whatsapp.url' => 'https://wa.test/send']);

        $this->assertInstanceOf(NodeApiGateway::class, app(WhatsAppGatewayInterface::class));
    }

    public function test_the_node_gateway_posts_the_message_to_the_service(): void
    {
        Http::fake(['https://wa.test/send' => Http::response(['ok' => true], 200)]);
        config(['services.whatsapp.driver' => 'node', 'services.whatsapp.url' => 'https://wa.test/send']);

        $ramesh = $this->driver('Ramesh', '9111111111', 15000);
        $this->delivered($ramesh, 1);

        app(DailyReportNotifier::class)->sendFor(now()->toDateString());

        Http::assertSent(fn ($request) => $request->url() === 'https://wa.test/send'
            && $request['to'] === self::ADMIN_NUMBER
            && $request['template'] === 'report.daily'
            && str_contains((string) $request['message'], 'Ramesh: 1 delivery'));

        $this->assertSame(
            WhatsappMessage::STATUS_SENT,
            WhatsappMessage::query()->latest('id')->firstOrFail()->status,
        );
    }

    /**
     * One refused attempt is **not** a dead message: six tries spread over
     * about fifty minutes remain, so the row stays pending with the reason
     * recorded rather than being announced as failed in the log. It is marked
     * failed only once that budget is spent — asserted in
     * {@see WhatsappDeliveryTest}.
     */
    public function test_a_failing_service_records_the_reason_and_waits_to_retry(): void
    {
        Http::fake(['https://wa.test/send' => Http::response('upstream down', 500)]);
        config(['services.whatsapp.driver' => 'node', 'services.whatsapp.url' => 'https://wa.test/send']);

        $this->driver('Ramesh', '9111111111', 15000);

        // The sync queue surfaces the job's exception; the row is still marked.
        try {
            app(DailyReportNotifier::class)->sendFor(now()->toDateString());
        } catch (\Throwable) {
            // expected — the job throws so the real queue would retry
        }

        $message = WhatsappMessage::query()->latest('id')->firstOrFail();

        $this->assertSame(WhatsappMessage::STATUS_PENDING, $message->status);
        $this->assertSame(1, $message->attempts);
        // The reason is what the log screen now shows, so recording it is the
        // assertion that matters either way.
        $this->assertStringContainsString('500', (string) $message->response);
    }

    private function driver(string $name, string $mobile, float $salary): User
    {
        $driver = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => $name, 'last_name' => '', 'mobile' => $mobile, 'monthly_salary' => $salary,
        ]);

        $tanker = Tanker::query()->create([
            'vehicle_name' => $name.' Tanker',
            'vehicle_number' => 'MH'.substr($mobile, 1, 8),
        ]);
        $tanker->assignments()->create([
            'driver_id' => $driver->id,
            'assigned_from' => now()->toDateString(),
        ]);

        return $driver->refresh();
    }

    private function delivered(User $driver, int $count, ?\DateTimeInterface $at = null): void
    {
        for ($i = 0; $i < $count; $i++) {
            Order::query()->create([
                'order_no' => 'TNK-TEST-'.str_pad((string) (Order::query()->count() + 1), 5, '0', STR_PAD_LEFT),
                'customer_id' => $this->customer->id,
                'driver_id' => $driver->id,
                'delivery_address' => 'Pune',
                'delivery_date' => now()->toDateString(),
                'order_amount' => 1000,
                'status' => OrderStatusEnum::Delivered,
                'delivered_at' => $at ?? now(),
            ]);
        }
    }
}
