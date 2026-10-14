<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers\Web;

use App\Modules\Auth\Contracts\AuthServiceInterface;
use App\Modules\Auth\Exceptions\AccountInactiveException;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Requests\WebLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * The panel is admin-only: a driver or customer with valid credentials is
 * still refused here, because their place is the mobile app.
 */
final class LoginController
{
    public function __construct(private readonly AuthServiceInterface $auth) {}

    public function __invoke(WebLoginRequest $request): RedirectResponse
    {
        try {
            $user = $this->auth->authenticate(
                $request->string('email')->value(),
                $request->string('password')->value(),
            );
        } catch (InvalidCredentialsException|AccountInactiveException $e) {
            return $this->failed($e->getMessage());
        }

        if (! $user->isAdmin()) {
            return $this->failed('This panel is for administrators only.');
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    private function failed(string $message): RedirectResponse
    {
        return back()
            ->withInput(['email' => request()->input('email')])
            ->withErrors(['email' => $message]);
    }
}
