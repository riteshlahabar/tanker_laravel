<?php

declare(strict_types=1);

namespace App\Modules\Auth\Exceptions;

use RuntimeException;

final class AccountInactiveException extends RuntimeException
{
    public function __construct(string $message = 'Your account has been deactivated.')
    {
        parent::__construct($message);
    }
}
