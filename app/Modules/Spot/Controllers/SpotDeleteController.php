<?php

declare(strict_types=1);

namespace App\Modules\Spot\Controllers;

use App\Modules\Spot\Contracts\SpotServiceInterface;
use App\Modules\Spot\Models\Spot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class SpotDeleteController
{
    public function __construct(private readonly SpotServiceInterface $spots) {}

    public function __invoke(Spot $spot): RedirectResponse
    {
        Gate::authorize('delete', $spot);

        $this->spots->delete($spot);

        return back()->with('success', __('Spot deleted successfully.'));
    }
}
