<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Modules\Setting\Contracts\SettingRepositoryInterface;

/**
 * Where the owner's notifications go. Settings win over .env, so the number
 * can be changed in the panel without a deploy.
 */
final class AdminRecipient
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    public function number(): ?string
    {
        $number = $this->settings->value('admin_whatsapp_number')
            ?: (string) config('services.whatsapp.admin_number');

        return trim($number) === '' ? null : trim($number);
    }
}
