<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One setting leaves the database; a second leaves only the screen.
 *
 * ⚠ **The filename is wider than what this does.** It was written to drop both
 * keys, and the owner then asked for `daily_report_time` to stay in the backend
 * with only its field removed. The name is kept because the migration may
 * already have run under it — renaming the file would make Laravel run a
 * "new" migration while this one stayed in the `migrations` table, and the old
 * copy would still delete the row.
 *
 * `default_monthly_salary` **is** deleted. It was only ever a pre-fill: what a
 * driver is paid is `users.monthly_salary`, a required field on their own form,
 * and the Driver Salary report reads nothing else — so a second figure on a
 * global screen could only disagree with the real ones while reading as though
 * it set them.
 *
 * `daily_report_time` **is kept**, untouched, at the owner's request. It is
 * removed from `setting::_partials.fields` and from the app's Settings screen,
 * and dropped from `UpdateSettingRequest` — a required rule for a field no form
 * posts any more would 422 every save. So it is stored and readable (the
 * settings API still returns it), simply not editable from either surface. The
 * 23:59 report itself is scheduled in `routes/console.php` and never read this
 * value.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'default_monthly_salary')->delete();
    }

    public function down(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'default_monthly_salary'],
            ['value' => '15000', 'updated_at' => now(), 'created_at' => now()],
        );
    }
};
