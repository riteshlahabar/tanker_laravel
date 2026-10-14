<?php

declare(strict_types=1);

namespace App\Modules\Customer\Contracts;

use App\Models\User;

interface CustomerServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * A blank password on update leaves the existing one untouched.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $customer, array $data): User;

    public function delete(User $customer): void;

    public function setStatus(User $customer, bool $status): User;
}
