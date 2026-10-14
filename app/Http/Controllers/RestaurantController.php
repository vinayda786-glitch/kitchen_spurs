<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Contracts\View\View;

class RestaurantController extends Controller
{
    /**
     * Render the Restaurant Menu page.
     */
    public function index(): View
    {
        $categories = MenuItem::where('is_available', true)
            ->distinct()
            ->pluck('category');

        $menuItems = MenuItem::where('is_available', true)
            ->orderBy('category')
            ->orderBy('price')
            ->get();

        return view('restaurant.index', [
            'categories' => $categories,
            'menuItems' => $menuItems,
        ]);
    }
}

