<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Notification\Contracts\WhatsAppGatewayInterface;
use App\Modules\Notification\Gateways\ScanApiGateway;
use App\Modules\Notification\Support\OutboundMessage;
use App\Modules\Notification\Support\PhoneNumber;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class WhatsappScanTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private const BASE = 'https://wa.example.test';

    private const KEY = 'test-key';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = $this->userWithRole(RoleEnum::Admin, ['email' => 'owner@example.test']);

        config()->set('services.whatsapp.driver', 'scan');
        config()->set('services.whatsapp_scan.base_url', self::BASE);
        config()->set('services.whatsapp_scan.api_key', self::KEY);
    }

    public function test_the_scan_screen_shows_the_qr_the_service_offers(): void
    {
        Http::fake([
            self::BASE.'/status' => Http::response(['connected' => false, 'state' => 'qr']),
            self::BASE.'/qr' => Http::response([
                'connected' => false,
                'qrImageDataUrl' => 'data:image/png;base64,AAAA',
            ]),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.whatsapp.scan'))
            ->assertOk()
            ->assertSee('data:image/png;base64,AAAA', false)
            ->assertSee('Pair WhatsApp');
    }

    public function test_a_paired_service_is_reported_instead_of_a_qr(): void
    {
        Http::fake([
            self::BASE.'/status' => Http::response([
                'connected' => true, 'state' => 'open', 'number' => '919812345678',
            ]),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.whatsapp.scan'))
            ->assertOk()
            ->assertSee('WhatsApp is connected')
            ->assertSee('919812345678');

        // Asking for a QR while paired would only be answered "not needed".
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/qr'));
    }

    public function test_a_rejected_session_tells_the_owner_to_log_out(): void
    {
        Http::fake([
            self::BASE.'/status' => Http::response([
                'connected' => false,
                'state' => 'reconnecting',
                'reconnectAttempt' => 97,
                'lastDisconnect' => ['code' => 401, 'reason' => 'Connection Failure'],
            ]),
            self::BASE.'/qr' => Http::response(['message' => 'QR not available yet.'], 404),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.whatsapp.scan'))
            ->assertOk()
            ->assertSee('WhatsApp has rejected the saved login');
    }

    public function test_the_state_endpoint_feeds_the_poller(): void
    {
        Http::fake([
            self::BASE.'/status' => Http::response(['connected' => false, 'state' => 'qr']),
            self::BASE.'/qr' => Http::response([
                'connected' => false, 'qrImageDataUrl' => 'data:image/png;base64,BBBB',
            ]),
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.whatsapp.scan.state'))
            ->assertOk()
            ->assertJsonPath('connected', false)
            ->assertJsonPath('qr_image', 'data:image/png;base64,BBBB');
    }

    public function test_the_gateway_posts_a_qualified_number_behind_the_api_key(): void
    {
        Http::fake([self::BASE.'/send' => Http::response(['success' => true, 'message' => 'Message sent'])]);

        $result = app(ScanApiGateway::class)->send(
            new OutboundMessage(to: '9812345678', template: 'order.placed', body: 'Your tanker is on the way.'),
        );

        $this->assertTrue($result->delivered);

        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE.'/send'
            && $request->hasHeader('x-api-key', self::KEY)
            // 91 added here rather than by the service, whose own prefixing
            // would leave a ten-digit number starting 91 untouched.
            && $request['phone'] === '919812345678'
            && $request['message'] === 'Your tanker is on the way.');
    }

    public function test_a_send_while_unpaired_fails_with_the_services_own_reason(): void
    {
        Http::fake([
            self::BASE.'/send' => Http::response([
                'success' => false,
                'message' => 'WhatsApp is not connected. Scan QR first.',
            ], 409),
        ]);

        $result = app(ScanApiGateway::class)->send(
            new OutboundMessage(to: '9812345678', template: 'order.placed', body: 'Hello'),
        );

        $this->assertFalse($result->delivered);
        $this->assertStringContainsString('Scan QR first', $result->response);
    }

    public function test_the_scan_driver_is_what_the_container_hands_out(): void
    {
        $this->assertInstanceOf(ScanApiGateway::class, app(WhatsAppGatewayInterface::class));
    }

    public function test_logging_out_goes_through_and_says_so(): void
    {
        Http::fake([self::BASE.'/logout' => Http::response(['success' => true, 'message' => 'Logged out'])]);

        $this->actingAs($this->admin)
            ->post(route('admin.whatsapp.scan.action'), ['action' => 'logout'])
            ->assertRedirect(route('admin.whatsapp.scan'))
            ->assertSessionHas('success');
    }

    public function test_a_number_already_carrying_the_country_code_is_left_alone(): void
    {
        $this->assertSame('919812345678', PhoneNumber::qualify('919812345678'));
        $this->assertSame('919812345678', PhoneNumber::qualify('+91 98123 45678'));
        $this->assertSame('919812345678', PhoneNumber::qualify('09812345678'));

        // The case the Node service gets wrong: a national number that starts
        // with the country code's own digits still needs the prefix.
        $this->assertSame('919123456789', PhoneNumber::qualify('9123456789'));

        $this->assertSame('', PhoneNumber::qualify(null));
        $this->assertFalse(PhoneNumber::isSendable('123'));
    }
}
