<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $currentMonth = now()->format('m'); // e.g. "05"
        $startOfWeek  = now()->startOfWeek()->toDateTimeString();

        // SQLite-compatible aggregated query (no MONTH() function)
        $stats = Order::where('user_id', $user->id)
            ->selectRaw("
                COUNT(*)                                                        AS total_orders,
                SUM(status IN ('pending','in progress'))                        AS pending_orders,
                SUM(status = 'in progress')                                     AS processing_orders,
                SUM(status = 'completed')                                       AS completed_orders,
                SUM(created_at >= ?)                                            AS orders_this_week,
                SUM(CASE WHEN status = 'completed'
                         AND strftime('%m', created_at) = ?
                         THEN total ELSE 0 END)                                 AS spent_month
            ", [
                $startOfWeek,
                $currentMonth,
            ])
            ->first();

        $total_orders      = (int)   ($stats->total_orders      ?? 0);
        $pending_orders    = (int)   ($stats->pending_orders     ?? 0);
        $processing_orders = (int)   ($stats->processing_orders  ?? 0);
        $completed_orders  = (int)   ($stats->completed_orders   ?? 0);
        $orders_this_week  = (int)   ($stats->orders_this_week   ?? 0);
        $spent_month       = (float) ($stats->spent_month        ?? 0);
        $balance           = $user->funds ?? 0;

        $success_rate = $total_orders > 0
            ? round(($completed_orders / $total_orders) * 100, 1)
            : 99.8;

        $recent_orders = Order::with('service')
            ->where('user_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        $categories = Category::where('status', 'active')->get();

        $services_by_category = Service::where('status', 'active')
            ->select(['id', 'name', 'rate', 'min', 'max', 'category_id'])
            ->get()
            ->groupBy('category_id')
            ->map(fn ($svcs) => $svcs->map(fn ($s) => [
                'id'   => $s->id,
                'name' => $s->name,
                'rate' => $s->rate,
                'min'  => $s->min,
                'max'  => $s->max,
            ]));

        return view('dashboard.index', compact(
            'balance',
            'total_orders',
            'pending_orders',
            'processing_orders',
            'completed_orders',
            'orders_this_week',
            'spent_month',
            'success_rate',
            'recent_orders',
            'categories',
            'services_by_category'
        ));
    }
}