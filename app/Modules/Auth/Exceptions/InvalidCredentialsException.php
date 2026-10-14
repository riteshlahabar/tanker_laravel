<?php

declare(strict_types=1);

namespace App\Modules\Auth\Exceptions;

use RuntimeException;

final class InvalidCredentialsException extends RuntimeException
{
    public function __construct(string $message = 'These credentials do not match our records.')
    {
        parent::__construct($message);
    }
}
