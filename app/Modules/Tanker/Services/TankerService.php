<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Services;

use App\Modules\Core\Services\BaseService;
use App\Modules\Tanker\Contracts\TankerRepositoryInterface;
use App\Modules\Tanker\Contracts\TankerServiceInterface;
use App\Modules\Tanker\Exceptions\TankerInUseException;
use App\Modules\Tanker\Models\Tanker;

final class TankerService extends BaseService implements TankerServiceInterface
{
    public function __construct(private readonly TankerRepositoryInterface $tankers) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tanker
    {
        return $this->transaction(fn (): Tanker => $this->tankers->store($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Tanker $tanker, array $data): Tanker
    {
        return $this->transaction(fn (): Tanker => $this->tankers->modify($tanker->id, $data));
    }

    /**
     * A tanker a driver is currently running must not disappear from under
     * their open orders.
     */
    public function delete(Tanker $tanker): void
    {
        if ($tanker->currentAssignment()->exists()) {
            throw new TankerInUseException;
        }

        $this->transaction(fn (): bool => $this->tankers->remove($tanker->id));
    }

    public function setStatus(Tanker $tanker, bool $status): Tanker
    {
        return $this->tankers->toggleStatus($tanker->id, $status);
    }
}
