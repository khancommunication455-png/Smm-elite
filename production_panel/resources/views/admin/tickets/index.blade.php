@extends('layouts.app')

@section('title', 'Support Tickets')
@section('page-title', 'Support Tickets')

@section('content')
<div class="flex-1 p-6">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-on-surface">Support Tickets</h1>
                <p class="text-on-surface-variant mt-1">Manage customer support requests</p>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="glass-card p-4 rounded-xl mb-6 border-l-4 border-tertiary bg-tertiary/5">
                <p class="text-tertiary font-medium">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Tickets Table --}}
        <div class="glass-card rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-outline-variant/30 bg-surface-container">
                            <th class="px-6 py-4 text-left">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Ticket ID</p>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">User</p>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Subject</p>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Status</p>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Created</p>
                            </th>
                            <th class="px-6 py-4 text-right">
                                <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Actions</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr class="border-b border-outline-variant/30 hover:bg-surface-container/50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-on-surface font-semibold">#{{ $ticket->id }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-on-surface font-medium">{{ $ticket->user->name }}</p>
                                    <p class="text-on-surface-variant text-xs">{{ $ticket->user->email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-on-surface truncate">{{ $ticket->subject }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $ticket->status === 'open' ? 'bg-primary/20 text-primary' : ($ticket->status === 'pending' ? 'bg-yellow/20 text-yellow' : 'bg-tertiary/20 text-tertiary') }}">
                                        {{ ucfirst($ticket->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-on-surface-variant text-sm">{{ $ticket->created_at->format('d M Y H:i') }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="#" onclick="openTicketDetail({{ $ticket->id }})" class="text-primary hover:text-primary/80 font-semibold text-sm transition-colors">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <span class="material-symbols-outlined text-[40px] text-outline-variant opacity-40 block mb-2">support_agent</span>
                                    <p class="text-on-surface-variant text-sm">No tickets yet</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $tickets->links() }}
        </div>
    </div>
</div>

<script>
function openTicketDetail(ticketId) {
    // This would typically open a modal or navigate to a detail page
    // For now, we'll just show an alert
    alert('Ticket detail view for #' + ticketId);
}
</script>
@endsection
