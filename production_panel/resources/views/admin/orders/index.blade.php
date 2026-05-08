@extends('layouts.app')

@section('title', 'Orders')
@section('page-title', 'Orders')

@section('content')
<div class="flex-1 p-6">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-on-surface">Orders</h1>
                <p class="text-on-surface-variant mt-1">Manage all customer orders</p>
            </div>
        </div>

        {{-- Filters & Search --}}
        <div class="glass-card p-4 rounded-xl mb-6 flex flex-col sm:flex-row gap-4">
            <form action="{{ route('admin.orders.index') }}" method="GET" class="flex gap-4 flex-1">
                <input type="text" name="search" placeholder="Search by ID or customer name..." 
                       value="{{ request('search') }}" class="glass-input flex-1">
                
                <select name="status" class="glass-input" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in progress" {{ request('status') === 'in progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                    <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Error</option>
                </select>

                <button type="submit" class="btn-primary px-6 rounded-lg">Search</button>
            </form>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="glass-card p-4 rounded-xl mb-6 border-l-4 border-tertiary bg-tertiary/5">
                <p class="text-tertiary font-medium">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Orders Table --}}
        <div class="glass-card rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-outline-variant/30 bg-surface-container">
                            <th class="px-6 py-4 text-left">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Order ID</p>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Customer</p>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Service</p>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Amount</p>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Status</p>
                            </th>
                            <th class="px-6 py-4 text-right">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Actions</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr class="border-b border-outline-variant/30 hover:bg-surface-container/50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-on-surface font-semibold">#{{ $order->id }}</p>
                                    <p class="text-on-surface-variant text-xs">{{ $order->created_at->format('d M Y') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-on-surface font-medium">{{ $order->user->name }}</p>
                                    <p class="text-on-surface-variant text-xs">{{ $order->user->email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-on-surface text-sm">{{ $order->service->name ?? 'N/A' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-on-surface font-semibold">${{ number_format($order->amount, 2) }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow/20 text-yellow',
                                                'in progress' => 'bg-primary/20 text-primary',
                                                'completed' => 'bg-tertiary/20 text-tertiary',
                                                'cancelled' => 'bg-error/20 text-error',
                                                'refunded' => 'bg-outline/20 text-outline',
                                                'partial' => 'bg-secondary/20 text-secondary',
                                                'error' => 'bg-error/20 text-error',
                                            ];
                                        @endphp
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$order->status] ?? 'bg-outline/20 text-outline' }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST" class="inline">
                                        @csrf
                                        <select name="status" onchange="this.form.submit()" 
                                                class="glass-input text-xs py-1 px-2 rounded-lg">
                                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="in progress" {{ $order->status === 'in progress' ? 'selected' : '' }}>In Progress</option>
                                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            <option value="refunded" {{ $order->status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                                            <option value="partial" {{ $order->status === 'partial' ? 'selected' : '' }}>Partial</option>
                                            <option value="error" {{ $order->status === 'error' ? 'selected' : '' }}>Error</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <span class="material-symbols-outlined text-[40px] text-outline-variant opacity-40 block mb-2">shopping_cart</span>
                                    <p class="text-on-surface-variant text-sm">No orders found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
