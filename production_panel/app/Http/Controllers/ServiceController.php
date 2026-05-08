<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Category;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $categories = \Illuminate\Support\Facades\Cache::remember('active_categories', 600, function () {
            return Category::where('status', 'active')->get();
        });

        $services = Service::with('category')
            ->where('status', 'active')
            ->when($request->q, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->cat, function ($query, $categoryId) {
                return $query->where('category_id', $categoryId);
            })
            ->paginate(50);

        return view('services.index', compact('services', 'categories'));
    }
}
