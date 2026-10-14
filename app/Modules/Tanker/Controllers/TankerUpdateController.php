<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Controllers;

use App\Modules\Tanker\Contracts\TankerServiceInterface;
use App\Modules\Tanker\Models\Tanker;
use App\Modules\Tanker\Requests\UpdateTankerRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class TankerUpdateController
{
    public function __construct(private readonly TankerServiceInterface $tankers) {}

    public function form(Tanker $tanker): View
    {
        Gate::authorize('update', $tanker);

        return view('tanker::edit', ['tanker' => $tanker]);
    }

    public function update(UpdateTankerRequest $request, Tanker $tanker): RedirectResponse
    {
        $this->tankers->update($tanker, $request->validated());

        return redirect()
            ->route('admin.tanker.index')
            ->with('success', __('Tanker updated successfully.'));
    }
}
