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
        // Water is sold by the tanker. This is the fallback price of one
        // tanker-load, used when the vehicle that delivered has no rate of its
        // own; zero means "use the flat default_order_amount" instead.
        'price_per_tanker' => '0',
        'company_name' => 'WTMS',
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
