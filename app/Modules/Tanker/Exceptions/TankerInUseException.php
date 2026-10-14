<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Exceptions;

use RuntimeException;

final class TankerInUseException extends RuntimeException
{
    public function __construct(string $message = 'This tanker is assigned to a driver. Reassign the driver first.')
    {
        parent::__construct($message);
    }
}
