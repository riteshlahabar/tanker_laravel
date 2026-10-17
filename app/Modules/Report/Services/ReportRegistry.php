<?php

declare(strict_types=1);

namespace App\Modules\Report\Services;

use App\Modules\Report\Contracts\ReportInterface;
use App\Modules\Report\Contracts\ReportRegistryInterface;
use App\Modules\Report\Reports\CustomerOrderReport;
use App\Modules\Report\Reports\DailyDeliveryReport;
use App\Modules\Report\Reports\DriverSalaryReport;
use App\Modules\Report\Reports\ScheduleAdherenceReport;
use App\Modules\Report\Reports\SpotRefillReport;
use App\Modules\Report\Reports\TankerUtilisationReport;
use InvalidArgumentException;

/**
 * Resolves a report by key, so one export route serves all of them and adding
 * a report means adding one line here.
 */
final class ReportRegistry implements ReportRegistryInterface
{
    /**
     * @var array<int, class-string<ReportInterface>>
     */
    private const REPORTS = [
        DriverSalaryReport::class,
        DailyDeliveryReport::class,
        SpotRefillReport::class,
        CustomerOrderReport::class,
        TankerUtilisationReport::class,
        ScheduleAdherenceReport::class,
    ];

    public function get(string $key): ReportInterface
    {
        return $this->all()[$key]
            ?? throw new InvalidArgumentException("No report is registered for [{$key}].");
    }

    /**
     * @return array<string, ReportInterface>
     */
    public function all(): array
    {
        $reports = [];

        foreach (self::REPORTS as $class) {
            $report = app($class);
            $reports[$report->key()] = $report;
        }

        return $reports;
    }
}
