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

/*
 * Standing orders. Generates **tomorrow's** deliveries just after midnight, so
 * the admin can assign drivers in the morning instead of on the day.
 */
Schedule::command('tanker:generate-scheduled-orders')
    ->dailyAt('00:05')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Create orders for standing-order customers that fall due tomorrow');
