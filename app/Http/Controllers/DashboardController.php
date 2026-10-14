<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Render the Analytics Dashboard interface.
     */
    public function index(): View
    {
        return view('analytics.dashboard');
    }
}

