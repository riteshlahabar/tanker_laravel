<?php

declare(strict_types=1);

use App\Modules\Order\Controllers\Api\Admin\AssignOrderController;
use App\Modules\Order\Controllers\Api\Admin\CreateOrderController;
use App\Modules\Order\Controllers\Api\Admin\OrderListController as AdminOrderListController;
use App\Modules\Order\Controllers\Api\Admin\ReassignOrderController;
use App\Modules\Order\Controllers\Api\CancelOrderController;
use App\Modules\Order\Controllers\Api\Customer\MyOrdersController;
use App\Modules\Order\Controllers\Api\Customer\PlaceOrderController;
use App\Modules\Order\Controllers\Api\Driver\MarkDeliveredController;
use App\Modules\Order\Controllers\Api\Driver\MarkOutForDeliveryController;
use App\Modules\Order\Controllers\Api\Driver\MyDeliveriesController;
use App\Modules\Order\Controllers\Api\Driver\PlaceDriverOrderController;
use App\Modules\Order\Controllers\Api\Driver\StartRefillController;
use App\Modules\Order\Controllers\Api\MarkOrderSeenController;
use App\Modules\Order\Controllers\Api\OrderAlertsController;
use App\Modules\Order\Controllers\Api\OrderDetailController;
use Illuminate\Support\Facades\Route;

/*
 * Order detail is shared by all three roles — the OrderPolicy decides what
 * each may see, so one controller serves them without widening access.
 */

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api'])->group(function (): void {

    Route::prefix('customer')->name('api.customer.')->middleware('role:customer')->group(function (): void {
        Route::get('orders', MyOrdersController::class)->name('orders.index');
        Route::post('orders', PlaceOrderController::class)->name('orders.store');
        Route::get('orders/{order}', OrderDetailController::class)->name('orders.show');
        Route::post('orders/{order}/cancel', CancelOrderController::class)->name('orders.cancel');
    });

    Route::prefix('driver')->name('api.driver.')->middleware('role:driver')->group(function (): void {
        Route::get('orders', MyDeliveriesController::class)->name('orders.index');
        // BEFORE `orders/{order}`, or the word "alerts" is matched as an
        // order id and the route 404s on a model it cannot bind.
        Route::get('orders/alerts', OrderAlertsController::class)->name('orders.alerts');
        // A job taken on the route. Placed and assigned to the caller in one
        // step — the driver is never asked which driver, see the request.
        Route::post('orders', PlaceDriverOrderController::class)->name('orders.store');
        Route::get('orders/{order}', OrderDetailController::class)->name('orders.show');
        Route::post('orders/{order}/refill', StartRefillController::class)->name('orders.refill');
        Route::post('orders/{order}/out-for-delivery', MarkOutForDeliveryController::class)
            ->name('orders.out-for-delivery');
        Route::post('orders/{order}/delivered', MarkDeliveredController::class)->name('orders.delivered');
        Route::post('orders/{order}/seen', MarkOrderSeenController::class)->name('orders.seen');
    });

    Route::prefix('admin')->name('api.admin.')->middleware('role:admin')->group(function (): void {
        Route::get('orders', AdminOrderListController::class)->name('orders.index');
        // Same ordering rule as the driver group above.
        Route::get('orders/alerts', OrderAlertsController::class)->name('orders.alerts');
        Route::post('orders', CreateOrderController::class)->name('orders.store');
        Route::get('orders/{order}', OrderDetailController::class)->name('orders.show');
        Route::post('orders/{order}/assign', AssignOrderController::class)->name('orders.assign');
        Route::post('orders/{order}/reassign', ReassignOrderController::class)->name('orders.reassign');
        Route::post('orders/{order}/cancel', CancelOrderController::class)->name('orders.cancel');
        Route::post('orders/{order}/seen', MarkOrderSeenController::class)->name('orders.seen');
    });
});
