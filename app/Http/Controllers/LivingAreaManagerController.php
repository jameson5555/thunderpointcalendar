<?php

namespace App\Http\Controllers;

use App\Http\Requests\LivingAreaManagerUpdateRequest;
use App\Models\LivingArea;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class LivingAreaManagerController extends Controller
{
    public function __invoke(LivingAreaManagerUpdateRequest $request, LivingArea $livingArea, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $role = $request->validated('role');

        if ($role === 'poobah') {
            $livingArea->managers()->syncWithoutDetaching([$user->id => ['role' => 'poobah']]);
            $livingArea->managers()->updateExistingPivot($user->id, ['role' => 'poobah']);
        } else {
            $livingArea->managers()->detach($user->id);
        }

        return redirect()
            ->route('admin.index')
            ->with('status', sprintf('%s is now %s for %s.', $user->name, $role === 'poobah' ? 'a Poobah' : 'standard', $livingArea->name));
    }
}