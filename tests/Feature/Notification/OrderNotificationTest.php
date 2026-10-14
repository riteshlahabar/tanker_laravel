<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Notification\Contracts\WhatsAppGatewayInterface;
use App\Modules\Notification\Jobs\SendWhatsAppMessage;
use App\Modules\Notification\Models\WhatsappMessage;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Models\Order;
use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class OrderNotificationTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private const ADMIN_NUMBER = '919812345678';

    private const CUSTOMER_NUMBER = '9333333333';

    private const DRIVER_NUMBER = '9111111111';

    private User $adminUser;

    private User $customer;

    private User $driver;

    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(SettingRepositoryInterface::class)->put('admin_whatsapp_number', self::ADMIN_NUMBER);

        $this->adminUser = $this->admin();
        $this->customer = $this->userWithRole(RoleEnum::Customer, [
            'first_name' => 'Sunita', 'last_name' => '', 'mobile' => self::CUSTOMER_NUMBER,
        ]);
        $this->driver = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Ramesh', 'last_name' => '', 'mobile' => self::DRIVER_NUMBER,
            'per_delivery_rate' => 150,
        ]);

        $tanker = Tanker::query()->create(['vehicle_name' => 'One', 'vehicle_number' => 'MH12AB1234']);
        $tanker->assignments()->create([
            'driver_id' => $this->driver->id,
            'assigned_from' => now()->toDateString(),
        ]);
        $this->driver->refresh();

        $this->spot = Spot::query()->create(['name' => 'Hadapsar Borewell', 'refill_charge' => 400]);
    }

    public function test_placing_an_order_messages_the_owner_and_the_customer(): void
    {
        $this->placeOrder();

        $messages = WhatsappMessage::query()->where('template', 'order.placed')->get();

        $this->assertCount(2, $messages);
        $this->assertEqualsCanonicalizing(
            [self::ADMIN_NUMBER, self::CUSTOMER_NUMBER],
            $messages->pluck('to')->all(),
        );
        $this->assertStringContainsString('New water tanker order', (string) $messages->first()->body);
    }

    public function test_assigning_messages_the_customer_and_the_driver_with_the_details(): void
    {
        $order = $this->placeOrder();
        WhatsappMessage::query()->delete();

        app(OrderServiceInterface::class)->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);

        $messages = WhatsappMessage::query()->where('template', 'order.assigned')->get();

        $this->assertEqualsCanonicalizing(
            [self::CUSTOMER_NUMBER, self::DRIVER_NUMBER],
            $messages->pluck('to')->all(),
        );

        $body = (string) $messages->first()->body;
        $this->assertStringContainsString('Ramesh', $body);
        $this->assertStringContainsString('MH12AB1234', $body);
        $this->assertStringContainsString('Hadapsar Borewell', $body);
    }

    public function test_delivery_messages_the_customer_and_the_owner(): void
    {
        $order = $this->deliverOrder();

        $messages = WhatsappMessage::query()->where('template', 'order.delivered')->get();

        $this->assertEqualsCanonicalizing(
            [self::CUSTOMER_NUMBER, self::ADMIN_NUMBER],
            $messages->pluck('to')->all(),
        );
        $this->assertStringContainsString('delivered', (string) $messages->first()->body);
    }

    public function test_every_step_of_the_road_is_announced(): void
    {
        $this->deliverOrder();

        $this->assertSame([
            'order.placed', 'order.assigned', 'order.refilling',
            'order.out-for-delivery', 'order.delivered',
        ], WhatsappMessage::query()->orderBy('id')->pluck('template')->unique()->values()->all());
    }

    public function test_cancelling_tells_the_customer_the_owner_and_the_driver(): void
    {
        $order = $this->placeOrder();
        app(OrderServiceInterface::class)->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);
        WhatsappMessage::query()->delete();

        app(OrderServiceInterface::class)->cancel($order, 'Customer not reachable', $this->adminUser);

        $messages = WhatsappMessage::query()->where('template', 'order.cancelled')->get();

        $this->assertEqualsCanonicalizing(
            [self::CUSTOMER_NUMBER, self::ADMIN_NUMBER, self::DRIVER_NUMBER],
            $messages->pluck('to')->all(),
        );
        $this->assertStringContainsString('Customer not reachable', (string) $messages->first()->body);
    }

    public function test_messages_are_queued_not_sent_inline(): void
    {
        Queue::fake();

        $this->placeOrder();

        Queue::assertPushed(SendWhatsAppMessage::class, 2);
    }

    public function test_nothing_is_sent_to_a_blank_number(): void
    {
        app(SettingRepositoryInterface::class)->put('admin_whatsapp_number', '');
        $this->customer->update(['mobile' => null]);

        $this->placeOrder();

        $this->assertSame(0, WhatsappMessage::query()->count());
    }

    public function test_the_same_number_is_not_messaged_twice_for_one_event(): void
    {
        // The owner testing the app as their own customer.
        app(SettingRepositoryInterface::class)->put('admin_whatsapp_number', self::CUSTOMER_NUMBER);

        $this->placeOrder();

        $this->assertSame(1, WhatsappMessage::query()->where('template', 'order.placed')->count());
    }

    public function test_a_message_is_marked_sent_once_the_gateway_accepts_it(): void
    {
        // The test queue is sync, so placing the order already ran the job.
        $this->placeOrder();

        $message = WhatsappMessage::query()->firstOrFail();

        $this->assertSame(WhatsappMessage::STATUS_SENT, $message->status);
        $this->assertSame(1, $message->attempts);
        $this->assertNotNull($message->sent_at);
        $this->assertSame('logged', $message->response);
    }

    public function test_a_pending_message_is_picked_up_by_the_job(): void
    {
        $message = WhatsappMessage::query()->create([
            'to' => self::ADMIN_NUMBER,
            'template' => 'test.manual',
            'body' => 'Hello',
            'status' => WhatsappMessage::STATUS_PENDING,
        ]);

        (new SendWhatsAppMessage($message->id))->handle(app(WhatsAppGatewayInterface::class));

        $this->assertSame(WhatsappMessage::STATUS_SENT, $message->refresh()->status);
    }

    public function test_an_already_sent_message_is_not_sent_again(): void
    {
        $message = WhatsappMessage::query()->create([
            'to' => self::ADMIN_NUMBER,
            'template' => 'test.manual',
            'body' => 'Hello',
            'status' => WhatsappMessage::STATUS_SENT,
            'attempts' => 1,
            'sent_at' => now()->subHour(),
        ]);

        (new SendWhatsAppMessage($message->id))->handle(app(WhatsAppGatewayInterface::class));

        $this->assertSame(1, $message->refresh()->attempts);
    }

    public function test_the_payload_carries_structured_order_data(): void
    {
        $order = $this->placeOrder();

        $payload = WhatsappMessage::query()->firstOrFail()->payload;

        $this->assertSame($order->order_no, $payload['order_no']);
        $this->assertSame('pending', $payload['status']);
        $this->assertEquals(1200, $payload['amount']);
        $this->assertSame('Sunita', $payload['customer']);
    }

    private function placeOrder(): Order
    {
        return app(OrderServiceInterface::class)->place([
            'customer_id' => $this->customer->id,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'quantity_litres' => 5000,
            'order_amount' => 1200,
        ], $this->adminUser);
    }

    private function deliverOrder(): Order
    {
        $order = $this->placeOrder();
        $service = app(OrderServiceInterface::class);

        $service->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);
        $service->moveTo($order, OrderStatusEnum::Refilling, $this->driver);
        $service->moveTo($order, OrderStatusEnum::OutForDelivery, $this->driver);
        $service->moveTo($order, OrderStatusEnum::Delivered, $this->driver);

        return $order;
    }
}
