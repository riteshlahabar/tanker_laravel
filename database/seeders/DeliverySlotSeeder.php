<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\DeliverySlot\Models\DeliverySlot;
use Illuminate\Database\Seeder;

final class DeliverySlotSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, start_time: string, end_time: string, sort_order: int}>
     */
    private const SLOTS = [
        ['name' => 'Morning', 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'sort_order' => 1],
        ['name' => 'Afternoon', 'start_time' => '12:00:00', 'end_time' => '16:00:00', 'sort_order' => 2],
        ['name' => 'Evening', 'start_time' => '16:00:00', 'end_time' => '20:00:00', 'sort_order' => 3],
    ];

    public function run(): void
    {
        foreach (self::SLOTS as $slot) {
            DeliverySlot::query()->firstOrCreate(['name' => $slot['name']], $slot);
        }
    }
}
