<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Controllers;

use App\Modules\Tanker\Contracts\TankerServiceInterface;
use App\Modules\Tanker\Exceptions\TankerInUseException;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class TankerDeleteController
{
    public function __construct(private readonly TankerServiceInterface $tankers) {}

    public function __invoke(Tanker $tanker): RedirectResponse
    {
        Gate::authorize('delete', $tanker);

        try {
            $this->tankers->delete($tanker);
        } catch (TankerInUseException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Tanker deleted successfully.'));
    }
}
