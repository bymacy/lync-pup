<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $startup = auth()->user()->startup;

        return view('startup.dashboard', compact('startup'));
    }
}
