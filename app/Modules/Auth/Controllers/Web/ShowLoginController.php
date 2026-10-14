<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers\Web;

use Illuminate\Contracts\View\View;

final class ShowLoginController
{
    public function __invoke(): View
    {
        return view('auth.login');
    }
}
