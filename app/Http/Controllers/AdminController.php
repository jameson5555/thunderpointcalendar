<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.index', [
            'livingAreas' => collect(config('thunderpoint.living_areas')),
        ]);
    }
}