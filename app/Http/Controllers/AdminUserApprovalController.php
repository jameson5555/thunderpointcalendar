<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminUserApprovalController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($user->approved_at === null) {
            $user->forceFill([
                'approved_at' => now(),
            ])->save();

            return redirect()->route('admin.index')
                ->with('status', sprintf('%s is now approved.', $user->name));
        }

        return redirect()->route('admin.index')
            ->with('status', sprintf('%s was already approved.', $user->name));
    }
}