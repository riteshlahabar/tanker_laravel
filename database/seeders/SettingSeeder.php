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
    /**
     * `daily_report_time` has no field on the Settings page any more, but it is
     * still seeded and still stored: the owner asked for it to stay in the
     * backend. Nothing reads it — the 23:59 report is scheduled in
     * `routes/console.php` — so it is a recorded intention, not a switch.
     */
    private const DEFAULTS = [
        'admin_whatsapp_number' => '',
        'company_name' => 'WTMS',
        'daily_report_time' => '23:59',
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
