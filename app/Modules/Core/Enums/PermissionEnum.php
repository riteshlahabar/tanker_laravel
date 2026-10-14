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

    case SpotView = 'spot.view';
    case SpotManage = 'spot.manage';

    case OrderView = 'order.view';
    case OrderCreate = 'order.create';
    case OrderAssign = 'order.assign';
    case OrderUpdateStatus = 'order.update-status';
    case OrderCancel = 'order.cancel';

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
                self::OrderUpdateStatus->value,
                self::SpotView->value,
            ],
            RoleEnum::Customer => [
                self::OrderView->value,
                self::OrderCreate->value,
                self::OrderCancel->value,
            ],
        };
    }
}
