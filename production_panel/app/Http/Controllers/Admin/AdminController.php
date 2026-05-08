<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Service;
use App\Models\Category;
use App\Models\ApiProvider;
use App\Models\Transaction;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\ActivityLog;
use App\Services\ProviderApiService;
use App\Services\ProviderSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct(
        private readonly ProviderSyncService $syncService,
    ) {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    // ─── Dashboard ──────────────────────────────────────────────────────────

    public function dashboard()
    {
        $total_orders        = Order::count();
        $pending_orders      = Order::whereIn('status', ['pending', 'in progress'])->count();
        $total_revenue       = Transaction::where('type', 'deposit')->where('status', 'completed')->sum('amount');
        $totalRevenue        = $total_revenue;
        $active_users        = User::where('status', 'active')->count();
        $activeUsers         = $active_users;
        $pendingTransactions = Transaction::where('status', 'pending')->count();
        $openTickets         = Ticket::where('status', '!=', 'closed')->count();
        $recent_orders       = Order::with(['user', 'service'])->latest()->take(10)->get();
        $recentOrders        = $recent_orders;
        $recent_users        = User::latest()->take(6)->get();
        $recentUsers         = $recent_users;
        $providers           = ApiProvider::withCount('services')->get();

        $stats = (object) [
            'total_orders'    => $total_orders,
            'pending_orders'  => $pending_orders,
            'completed_orders' => Order::where('status', 'completed')->count(),
        ];

        return view('admin.dashboard', compact(
            'stats',
            'total_orders',
            'pending_orders',
            'total_revenue',
            'totalRevenue',
            'active_users',
            'activeUsers',
            'pendingTransactions',
            'openTickets',
            'recent_orders',
            'recentOrders',
            'recent_users',
            'recentUsers',
            'providers'
        ));
    }

    // ─── Services ───────────────────────────────────────────────────────────

    public function servicesIndex(Request $request)
    {
        $query = Service::with('apiProvider');

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'price':
                $query->orderBy('rate', $sortDirection);
                break;
            case 'delivery_time':
                $query->orderBy('min_time', $sortDirection);
                break;
            default:
                $query->orderBy('name', 'asc');
        }

        // Filtering by tier
        if ($request->has('tier') && $request->tier !== '') {
            $query->where('tier', $request->tier);
        }

        $services = $query->paginate(50)->withQueryString();

        return view('admin.services.index', compact('services'));
    }

    public function servicesToggle(Service $service)
    {
        $service->update([
            'status' => $service->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Service status updated.');
    }

    // ─── Providers ──────────────────────────────────────────────────────────

    public function providersIndex()
    {
        $providers = ApiProvider::withCount('services')->get();
        return view('admin.providers.index', compact('providers'));
    }

    public function providersCreate()
    {
        return view('admin.providers.create');
    }

    public function providersStore(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100|unique:api_providers',
            'url'                 => 'required|url|max:255',
            'api_key'             => 'required|string|max:255',
            'percentage_increase' => 'required|numeric|min:0|max:10000',
        ]);

        try {
            $provider = ApiProvider::create($validated + ['status' => 'active']);
            Log::info('API Provider created', ['provider_id' => $provider->id, 'admin_id' => Auth::id()]);
            return redirect()->route('admin.providers.index')
                ->with('success', 'Provider added. Click Sync to import services.');
        } catch (\Exception $e) {
            Log::error('Provider creation failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to create provider.']);
        }
    }

    public function providersEdit(ApiProvider $provider)
    {
        return view('admin.providers.edit', compact('provider'));
    }

    public function providersUpdate(Request $request, ApiProvider $provider)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100|unique:api_providers,name,' . $provider->id,
            'url'                 => 'required|url|max:255',
            'api_key'             => 'required|string|max:255',
            'percentage_increase' => 'required|numeric|min:0|max:10000',
            'status'              => 'required|in:active,inactive',
        ]);

        try {
            $provider->update($validated);
            Log::info('API Provider updated', ['provider_id' => $provider->id, 'admin_id' => Auth::id()]);
            return back()->with('success', 'Provider updated.');
        } catch (\Exception $e) {
            Log::error('Provider update failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to update provider.']);
        }
    }

    // ─── Sync ───────────────────────────────────────────────────────────────

    public function syncProvider(ApiProvider $provider)
    {
        try {
            $synced = $this->syncService->syncProvider($provider);
            Log::info('Provider synced', ['provider_id' => $provider->id, 'count' => $synced, 'admin_id' => Auth::id()]);
            return back()->with('success', "Synced {$synced} services from {$provider->name}.");
        } catch (\Throwable $e) {
            Log::error('Provider sync failed: ' . $e->getMessage(), ['provider_id' => $provider->id]);
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function syncAll()
    {
        $synced = $this->syncService->syncAll();
        Log::info('All providers synced', ['count' => $synced, 'admin_id' => Auth::id()]);
        return response()->json(['message' => "Synced {$synced} services."]);
    }

    public function syncServices()
    {
        return $this->syncAll();
    }

    public function syncOrders()
    {
        try {
            $updated = 0;

            Order::whereIn('status', ['pending', 'in progress'])
                ->whereNotNull('api_order_id')
                ->with('service.apiProvider')
                ->chunkById(100, function ($orders) use (&$updated) {
                    $byProvider = $orders->groupBy(fn ($o) => optional($o->service?->apiProvider)->id);

                    foreach ($byProvider as $providerId => $group) {
                        $provider = $group->first()->service?->apiProvider;
                        if (! $provider) {
                            continue;
                        }

                        try {
                            $ids      = $group->pluck('api_order_id')->toArray();
                            $api      = new ProviderApiService($provider);
                            $response = $api->getStatusBulk($ids);

                            if (! is_array($response)) {
                                continue;
                            }

                            $upserts = [];
                            foreach ($group as $order) {
                                $data = $response[$order->api_order_id] ?? null;
                                if (! $data) {
                                    continue;
                                }

                                $newStatus = $this->mapStatus($data['status'] ?? '');
                                if ($newStatus !== $order->status) {
                                    $upserts[] = [
                                        'id'      => $order->id,
                                        'status'  => $newStatus,
                                        'remains' => $data['remains'] ?? $order->remains,
                                    ];
                                    $updated++;
                                }
                            }

                            if ($upserts) {
                                Order::upsert($upserts, ['id'], ['status', 'remains']);
                            }
                        } catch (\Throwable $e) {
                            Log::warning("Order sync failed for provider {$providerId}: " . $e->getMessage());
                        }
                    }
                });

            Log::info('Orders sync completed', ['updated' => $updated, 'admin_id' => Auth::id()]);
            return response()->json(['message' => "Updated {$updated} orders."]);
        } catch (\Exception $e) {
            Log::error('Order sync failed: ' . $e->getMessage());
            return response()->json(['error' => 'Sync failed'], 500);
        }
    }

    // ─── Orders ─────────────────────────────────────────────────────────────

    public function ordersIndex(Request $request)
    {
        $orders = Order::with(['user', 'service'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where('id', $s)
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")))
            ->latest()
            ->paginate(30);

        return view('admin.orders.index', compact('orders'));
    }

    public function ordersUpdateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in progress,completed,cancelled,refunded,partial,error',
        ]);

        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        Log::info('Order status updated', [
            'order_id'   => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'admin_id'   => Auth::id(),
        ]);

        return back()->with('success', "Order #{$order->id} updated to {$validated['status']}.");
    }

    // ─── Users ──────────────────────────────────────────────────────────────

    public function usersIndex(Request $request)
    {
        $users = User::withCount('orders')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            ->latest()
            ->paginate(30);

        return view('admin.users.index', compact('users'));
    }

    public function usersAddFunds(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:1000000',
            'note'   => 'nullable|string|max:200',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->increment('funds', $validated['amount']);
            Transaction::create([
                'user_id'     => $user->id,
                'amount'      => $validated['amount'],
                'type'        => 'deposit',
                'status'      => 'completed',
                'description' => 'Admin credit: ' . ($validated['note'] ?? 'Manual top-up'),
            ]);
            Log::info('Admin added funds', [
                'user_id'  => $user->id,
                'amount'   => $validated['amount'],
                'admin_id' => Auth::id(),
            ]);
        });

        return back()->with('success', "PKR {$validated['amount']} added to {$user->name}'s account.");
    }

    public function usersBan(Request $request, User $user)
    {
        if ($user->is_admin) {
            return back()->withErrors(['error' => 'Cannot ban admin users.']);
        }

        $user->update(['status' => 'banned']);
        Log::warning('User banned', ['user_id' => $user->id, 'admin_id' => Auth::id()]);

        return back()->with('success', "User {$user->name} has been banned.");
    }

    public function usersUnban(Request $request, User $user)
    {
        $user->update(['status' => 'active']);
        Log::info('User unbanned', ['user_id' => $user->id, 'admin_id' => Auth::id()]);

        return back()->with('success', "User {$user->name} has been unbanned.");
    }

    // ─── Transactions ────────────────────────────────────────────────────────

    public function transactionsIndex(Request $request)
    {
        $transactions = Transaction::with('user')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(30);

        return view('admin.transactions.index', compact('transactions'));
    }

    public function transactionsApprove(Request $request, Transaction $transaction)
    {
        try {
            DB::transaction(function () use ($transaction) {
                $tx = Transaction::lockForUpdate()->findOrFail($transaction->id);

                if ($tx->status !== 'pending') {
                    throw new \RuntimeException('Transaction already processed.');
                }

                $tx->user->increment('funds', $tx->amount);
                $tx->update(['status' => 'completed']);

                Log::info('Transaction approved', [
                    'transaction_id' => $tx->id,
                    'amount'         => $tx->amount,
                    'admin_id'       => Auth::id(),
                ]);
            });

            return back()->with('success', 'Transaction approved — funds credited.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Transaction approval failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to approve transaction.']);
        }
    }

    public function transactionsReject(Request $request, Transaction $transaction)
    {
        try {
            DB::transaction(function () use ($transaction) {
                $tx = Transaction::lockForUpdate()->findOrFail($transaction->id);

                if ($tx->status !== 'pending') {
                    throw new \RuntimeException('Transaction already processed.');
                }

                $tx->update(['status' => 'failed']);

                Log::info('Transaction rejected', ['transaction_id' => $tx->id, 'admin_id' => Auth::id()]);
            });

            return back()->with('success', 'Transaction rejected.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Transaction rejection failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to reject.']);
        }
    }

    // ─── Tickets ────────────────────────────────────────────────────────────

    public function ticketsIndex()
    {
        $tickets = Ticket::with('user')->latest()->paginate(20);
        return view('admin.tickets.index', compact('tickets'));
    }

    public function ticketsReply(Request $request, Ticket $ticket)
    {
        $validated = $request->validate(['message' => 'required|string|min:3|max:5000']);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'message'   => $validated['message'],
            'is_admin'  => true,
        ]);
        $ticket->update(['status' => 'pending']);

        Log::info('Admin replied to ticket', ['ticket_id' => $ticket->id, 'admin_id' => Auth::id()]);

        return back()->with('success', 'Reply sent.');
    }

    public function ticketsClose(Ticket $ticket)
    {
        $ticket->update(['status' => 'closed']);
        Log::info('Ticket closed', ['ticket_id' => $ticket->id, 'admin_id' => Auth::id()]);

        return back()->with('success', 'Ticket closed.');
    }

    // ─── Logs ────────────────────────────────────────────────────────────────

    public function activityLogs(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->user_id, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->latest()
            ->paginate(50);

        return view('admin.logs.activity', compact('logs'));
    }

    public function paymentLogs(Request $request)
    {
        $logs = DB::table('payment_logs')
            ->when($request->user_id, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(50);

        return view('admin.logs.payments', compact('logs'));
    }

    public function providerLogs(Request $request)
    {
        $logs = DB::table('provider_logs')
            ->when($request->provider_id, fn ($q, $id) => $q->where('api_provider_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(50);

        return view('admin.logs.providers', compact('logs'));
    }

    // ─── Settings ────────────────────────────────────────────────────────────

    public function settings()
    {
        return view('admin.settings');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function mapStatus(string $raw): string
    {
        return match (strtolower(trim($raw))) {
            'completed'                 => 'completed',
            'partial'                   => 'partial',
            'cancelled', 'canceled'     => 'cancelled',
            'processing', 'in progress' => 'in progress',
            'error', 'fail', 'failed'   => 'error',
            default                     => 'pending',
        };
    }
}