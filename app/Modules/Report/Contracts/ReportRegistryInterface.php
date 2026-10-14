<?php

declare(strict_types=1);

namespace App\Modules\Report\Contracts;

interface ReportRegistryInterface
{
    /**
     * @throws \InvalidArgumentException when no report answers to that key
     */
    public function get(string $key): ReportInterface;

    /**
     * @return array<string, ReportInterface>
     */
    public function all(): array;
}
