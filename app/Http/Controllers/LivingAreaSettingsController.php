<?php

namespace App\Http\Controllers;

use App\Http\Requests\LivingAreaSettingsUpdateRequest;
use App\Models\LivingArea;
use Illuminate\Http\RedirectResponse;

class LivingAreaSettingsController extends Controller
{
    public function __invoke(LivingAreaSettingsUpdateRequest $request, LivingArea $livingArea): RedirectResponse
    {
        abort_unless($request->user()?->managesArea($livingArea->id), 403);

        $livingArea->update($request->validated());

        return redirect()
            ->route('admin.index')
            ->with('status', sprintf('%s settings updated.', $livingArea->name));
    }
}