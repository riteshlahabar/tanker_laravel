<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Customer\Contracts\CustomerRepositoryInterface;

/**
 * Falls back to the address the admin entered when the customer was added.
 *
 * The owner's rule: a booking should not have to be told where to deliver —
 * the customer's own address is already on file, so it is the default.
 *
 * Resolved here, in the one creation path, rather than in each form: the app's
 * booking screen, the admin's New Order and the nightly generator then cannot
 * disagree about what "no address given" means, exactly as `delivery_at` is
 * resolved once in [DeliveryAtResolver].
 *
 * It used to carry the customer's coordinates across with the address, when the
 * two matched. Coordinates are gone from the project — nothing ever filled them
 * in but an admin typing two decimals by hand, and no screen read them back —
 * so an address is all there is to resolve.
 */
final class CustomerAddressResolver
{
    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    /**
     * @param  array<string, mixed>  $data  an order's attributes
     * @return array<string, mixed> the same attributes, with the delivery
     *                              address taken from the customer when the
     *                              order does not name one
     */
    public function apply(array $data): array
    {
        $customer = $this->customerFor($data);

        if ($customer === null) {
            return $data;
        }

        if (blank($data['delivery_address'] ?? null)) {
            $data['delivery_address'] = $customer->address;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function customerFor(array $data): ?User
    {
        $id = $data['customer_id'] ?? null;

        if ($id === null || $id === '') {
            return null;
        }

        // Through the customer-scoped query, so a driver's or admin's user id
        // can never be read as a customer here.
        $customer = $this->customers->scopedQuery()
            ->select(['id', 'address'])
            ->find((int) $id);

        return $customer instanceof User ? $customer : null;
    }
}
