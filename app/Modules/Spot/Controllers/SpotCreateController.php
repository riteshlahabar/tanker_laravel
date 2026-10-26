<?php

declare(strict_types=1);

namespace App\Modules\Spot\Controllers;

use App\Modules\Spot\Contracts\SpotServiceInterface;
use App\Modules\Spot\Models\Spot;
use App\Modules\Spot\Requests\StoreSpotRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class SpotCreateController
{
    public function __construct(private readonly SpotServiceInterface $spots) {}

    public function form(): View
    {
        Gate::authorize('create', Spot::class);

        return view('spot::create');
    }

    public function store(StoreSpotRequest $request): RedirectResponse
    {
        $this->spots->create($request->validated());

        return redirect()
            ->route('admin.spot.index')
            ->with('success', __('Filling point added successfully.'));
    }
}
