<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Models\User;
use App\Modules\Core\Services\BaseService;
use App\Modules\Customer\Contracts\CustomerRepositoryInterface;
use App\Modules\Customer\Contracts\CustomerServiceInterface;

final class CustomerService extends BaseService implements CustomerServiceInterface
{
    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return $this->transaction(fn (): User => $this->customers->store($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $customer, array $data): User
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $this->transaction(fn (): User => $this->customers->modify($customer->id, $data));
    }

    /**
     * A hard delete, at the owner's instruction (2026-10-26): the row leaves
     * `users` outright rather than being stamped `deleted_at`.
     *
     * Two consequences, both deliberate. `orders.customer_id` and
     * `delivery_schedules.customer_id` are `cascadeOnDelete`, so the customer's
     * orders, their status logs, their standing orders and the skips on them go
     * with them — their trade disappears from every report covering a past
     * date. And the mobile number is freed immediately, which is what lets the
     * same number be registered again.
     */
    public function delete(User $customer): void
    {
        $this->transaction(function () use ($customer): void {
            $customer->tokens()->delete();
            $this->customers->purge($customer->id);
        });
    }

    public function setStatus(User $customer, bool $status): User
    {
        if (! $status) {
            $customer->tokens()->delete();
        }

        return $this->customers->toggleStatus($customer->id, $status);
    }
}
