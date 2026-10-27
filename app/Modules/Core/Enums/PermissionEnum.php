<?php

declare(strict_types=1);

namespace App\Modules\Core\Enums;

/**
 * Permission names follow "<module>.<action>". Roles are granted sets of these
 * by RolePermissionSeeder; policies and route middleware check them.
 */
enum PermissionEnum: string
{
    case TankerView = 'tanker.view';
    case TankerManage = 'tanker.manage';

    case DriverView = 'driver.view';
    case DriverManage = 'driver.manage';

    case CustomerView = 'customer.view';
    case CustomerManage = 'customer.manage';
    // Enough of a customer to write an order for them — name, mobile, address
    // — and nothing else. Deliberately *not* CustomerView, which opens the
    // full record and the panel's customer screens: a driver placing an order
    // needs to find a customer, not read their account.
    case CustomerLookup = 'customer.lookup';

    case SpotView = 'spot.view';
    case SpotManage = 'spot.manage';

    case SlotView = 'slot.view';
    case SlotManage = 'slot.manage';

    case ScheduleView = 'schedule.view';
    case ScheduleManage = 'schedule.manage';
    // A fixed-schedule customer calling off a single day of their own routine.
    case ScheduleSkip = 'schedule.skip';

    case OrderView = 'order.view';
    case OrderCreate = 'order.create';
    case OrderAssign = 'order.assign';
    case OrderUpdateStatus = 'order.update-status';
    case OrderCancel = 'order.cancel';

    case ExpenseView = 'expense.view';
    case ExpenseCreate = 'expense.create';
    // Editing or deleting somebody else's expense — the admin's override.
    case ExpenseManage = 'expense.manage';

    case ReportView = 'report.view';
    case ReportExport = 'report.export';

    case SettingManage = 'setting.manage';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Permissions granted to each role.
     *
     * @return array<int, string>
     */
    public static function forRole(RoleEnum $role): array
    {
        return match ($role) {
            RoleEnum::Admin => self::values(),
            RoleEnum::Driver => [
                self::OrderView->value,
                // A driver flagged down on the route writes the order himself,
                // and `DriverPlaceOrderRequest` forces it onto *his* id — so
                // OrderCreate here can only ever create his own work. Note
                // OrderAssign is **not** granted: `OrderService::assign()`
                // runs no permission check of its own, so creation is all this
                // path needs, and withholding assign keeps a driver off the
                // admin's assign and reassign endpoints.
                self::OrderCreate->value,
                self::OrderUpdateStatus->value,
                // Finding that customer. Not CustomerView — see the case.
                self::CustomerLookup->value,
                self::SpotView->value,
                self::SlotView->value,
                // Their own out-of-pocket spending. No ExpenseManage: a driver
                // may only ever touch their own rows, which the policy and the
                // repository scope enforce on top of this.
                self::ExpenseView->value,
                self::ExpenseCreate->value,
            ],
            RoleEnum::Customer => [
                self::OrderView->value,
                self::OrderCreate->value,
                self::OrderCancel->value,
                self::SlotView->value,
                // Read their own routine and call off a single day of it.
                self::ScheduleView->value,
                self::ScheduleSkip->value,
            ],
        };
    }
}
