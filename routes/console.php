<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
 * The owner's daily WhatsApp summary. Needs the scheduler running:
 *   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
 */
Schedule::command('tanker:daily-report')
    ->dailyAt('23:59')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->onOneServer()
    ->description("Send the day's driver delivery and earning summary to the owner");
