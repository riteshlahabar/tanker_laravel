<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Setting\Models\Setting;
use Illuminate\Database\Seeder;

final class SettingSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const DEFAULTS = [
        'admin_whatsapp_number' => '',
        'daily_report_time' => '23:59',
        'default_driver_rate' => '150',
        'default_order_amount' => '0',
        // Zero means "use the flat default_order_amount"; set a rate to price
        // customer orders by the litre instead.
        'price_per_litre' => '0',
        'company_name' => 'Tanker',
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
