<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Controllers;

use App\Modules\Tanker\Contracts\TankerServiceInterface;
use App\Modules\Tanker\Models\Tanker;
use App\Modules\Tanker\Requests\StoreTankerRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class TankerCreateController
{
    public function __construct(private readonly TankerServiceInterface $tankers) {}

    public function form(): View
    {
        Gate::authorize('create', Tanker::class);

        return view('tanker::create');
    }

    public function store(StoreTankerRequest $request): RedirectResponse
    {
        $this->tankers->create($request->validated());

        return redirect()
            ->route('admin.tanker.index')
            ->with('success', __('Tanker added successfully.'));
    }
}
