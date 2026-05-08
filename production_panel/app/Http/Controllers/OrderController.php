<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\ProviderApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OrderController
 *
 * FIXES:
 * - LOW-1: Creates a Transaction (deduction) record for every order placed
 * - Validates service is still active at time of purchase (not just at form load)
 * - Validates quantity within service min/max before DB transaction
 * - Adds IP logging to order context
 */
class OrderController extends Controller
{
    public function __construct(protected \App\Services\OrderService $orderService)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $orders = Order::with('service')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        // Only load active services with their categories — avoid loading all columns
        $services = Service::with('category')
            ->where('status', 'active')
            ->orderBy('category_id')
            ->get();

        $categories = Category::where('status', 'active')->get();

        return view('orders.create', compact('services', 'categories'));
    }

    public function store(\App\Http\Requests\StoreOrderRequest $request)
    {
        $validated = $request->validated();

        // Check for duplicate order within 60 seconds
        if ($this->orderService->isDuplicateOrder(Auth::id(), $validated['service_id'], $validated['link'])) {
            return back()->withErrors(['error' => 'You already placed an identical order a moment ago. Please wait 60 seconds.']);
        }

        try {
            $order = $this->orderService->createOrder($validated);

            Log::info('Order placed', [
                'order_id'   => $order->id,
                'user_id'    => Auth::id(),
                'service_id' => $validated['service_id'],
                'total'      => $order->total,
                'ip'         => $request->ip(),
            ]);

            return redirect()->route('orders.show', $order->id)
                ->with('success', "Order #{$order->id} placed successfully!");

        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);
        $order->load('service');

        return view('orders.show', compact('order'));
    }
}
