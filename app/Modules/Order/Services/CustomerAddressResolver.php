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
 */
final class CustomerAddressResolver
{
    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    /**
     * @param  array<string, mixed>  $data  an order's attributes
     * @return array<string, mixed> the same attributes, with the delivery
     *                              address — and, when the delivery is to that
     *                              registered address, its coordinates — taken
     *                              from the customer
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

        return $this->withPoint($data, $customer);
    }

    /**
     * The customer's own latitude and longitude, but **only** when the order
     * is going to the address they are registered at.
     *
     * A customer may book a one-off delivery somewhere else — a site, a
     * relative's house — and the form keeps the address editable for exactly
     * that. Copying their home point onto such an order would hand the driver
     * a map pin for the wrong place, which is worse than no pin at all.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withPoint(array $data, User $customer): array
    {
        $hasPoint = ($data['latitude'] ?? null) !== null && ($data['longitude'] ?? null) !== null;

        if ($hasPoint || $customer->latitude === null || $customer->longitude === null) {
            return $data;
        }

        if (! $this->isRegisteredAddress($data['delivery_address'] ?? null, $customer->address)) {
            return $data;
        }

        return [
            ...$data,
            'latitude' => $customer->latitude,
            'longitude' => $customer->longitude,
        ];
    }

    private function isRegisteredAddress(mixed $given, ?string $registered): bool
    {
        if (! is_string($given) || $registered === null) {
            return false;
        }

        // Compared rather than assumed: the app pre-fills the field, so an
        // untouched form sends back the registered address word for word and
        // still deserves the point.
        return mb_strtolower(trim($given)) === mb_strtolower(trim($registered));
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
            ->select(['id', 'address', 'latitude', 'longitude'])
            ->find((int) $id);

        return $customer instanceof User ? $customer : null;
    }
}
