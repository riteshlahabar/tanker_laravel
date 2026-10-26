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
        'default_monthly_salary' => '15000',
        // Water is sold by the tanker. This is the fallback price of one
        // tanker-load, used when the vehicle that delivered has no rate of
        // its own. Zero means pricing is not configured yet.
        'default_tanker_rate' => '0',
        'company_name' => 'WTMS',
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
