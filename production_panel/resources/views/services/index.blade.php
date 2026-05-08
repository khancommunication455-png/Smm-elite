@extends('layouts.app')
@section('title', 'Services & Pricing')
@section('page-title', 'Services & Pricing')

@section('content')

{{-- Header + search --}}
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 fade-up">
    <div>
        <h1 class="font-h1 text-on-surface" style="font-size:32px">Services &amp; Pricing</h1>
        <p class="text-on-surface-variant text-sm mt-1">{{ $services->total() }} services across {{ $categories->count() }} categories</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-2.5 text-outline text-[18px] pointer-events-none">search</span>
            <input type="text" id="svc-search" placeholder="Search services..."
                class="glass-input bg-transparent pl-10 pr-4 py-2.5 rounded-lg border border-outline-variant/40 focus:border-primary text-sm w-64 transition-colors"
                oninput="filterServices()">
        </div>
    </div>
</div>

{{-- Category filter tabs --}}
<div class="flex gap-2 flex-wrap mb-6 fade-up">
    <button onclick="filterCat('')" class="cat-btn active px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider border border-outline-variant/40 text-on-surface bg-white/5 transition-all" data-cat="">
        All
    </button>
    @foreach($categories as $cat)
    <button onclick="filterCat('{{ $cat->id }}')" class="cat-btn px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider border border-outline-variant/30 text-outline hover:text-on-surface hover:bg-white/5 transition-all" data-cat="{{ $cat->id }}">
        {{ $cat->name }}
    </button>
    @endforeach
</div>

{{-- Services table --}}
<div class="glass-card rounded-xl overflow-hidden fade-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="svc-table">
            <thead>
                <tr class="border-b border-outline-variant/30">
                    <th class="px-5 py-3 font-label-caps text-label-caps text-outline font-normal">Service</th>
                    <th class="px-5 py-3 font-label-caps text-label-caps text-outline font-normal">Category</th>
                    <th class="px-5 py-3 font-label-caps text-label-caps text-outline font-normal text-right">Rate / 1K</th>
                    <th class="px-5 py-3 font-label-caps text-label-caps text-outline font-normal text-right hidden sm:table-cell">PKR / 1K</th>
                    <th class="px-5 py-3 font-label-caps text-label-caps text-outline font-normal text-right hidden md:table-cell">Min</th>
                    <th class="px-5 py-3 font-label-caps text-label-caps text-outline font-normal text-right hidden md:table-cell">Max</th>
                    <th class="px-5 py-3 font-label-caps text-label-caps text-outline font-normal text-center">Tier</th>
                    <th class="px-5 py-3 font-label-caps text-label-caps text-outline font-normal text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($services as $svc)
                <tr class="svc-row border-b border-surface-container-high hover:bg-white/5 transition-colors"
                    data-name="{{ strtolower($svc->name) }}"
                    data-cat="{{ $svc->category_id }}">
                    <td class="px-5 py-4">
                        <p class="text-on-surface font-medium text-sm">{{ $svc->name }}</p>
                        @if($svc->description)
                        <p class="text-outline text-xs mt-0.5 max-w-xs truncate">{{ $svc->description }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-on-surface-variant text-sm">{{ $svc->category->name ?? '—' }}</td>
                    <td class="px-5 py-4 text-right">
                        <span class="text-primary font-bold font-inter">${{ number_format($svc->rate, 4) }}</span>
                    </td>
                    <td class="px-5 py-4 text-right text-tertiary text-sm hidden sm:table-cell">
                        ₨{{ number_format($svc->rate * session('usd_pkr_rate',280), 1) }}
                    </td>
                    <td class="px-5 py-4 text-right text-outline text-sm hidden md:table-cell">{{ number_format($svc->min) }}</td>
                    <td class="px-5 py-4 text-right text-outline text-sm hidden md:table-cell">{{ number_format($svc->max) }}</td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider border
                            {{ $svc->rate < 0.5 ? 'bg-tertiary/10 text-tertiary border-tertiary/30' : ($svc->rate < 1.5 ? 'bg-primary/10 text-primary border-primary/30' : 'bg-secondary/10 text-secondary border-secondary/30') }}">
                            {{ $svc->rate < 0.5 ? 'Economy' : ($svc->rate < 1.5 ? 'Standard' : 'Premium') }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <a href="{{ route('orders.create') }}?service={{ $svc->id }}"
                            class="inline-flex items-center gap-1 bg-gradient-primary text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:brightness-110 transition-all neon-glow-primary">
                            <span class="material-symbols-outlined text-[14px]">add</span> Order
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-16 text-center text-outline">
                    <span class="material-symbols-outlined text-[48px] block mb-3 opacity-20">inventory_2</span>
                    <p class="mb-2">No services available yet.</p>
                    @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.providers.index') }}" class="text-primary text-sm hover:underline">Sync from API provider →</a>
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($services->hasPages())
    <div class="px-5 py-4 border-t border-outline-variant/30 flex items-center justify-between">
        <p class="text-xs text-outline">Showing {{ $services->firstItem() }}–{{ $services->lastItem() }} of {{ $services->total() }}</p>
        <div class="flex gap-1">
            @if($services->onFirstPage())
            <span class="px-3 py-1.5 text-xs text-outline border border-outline-variant/30 rounded-lg opacity-40">← Prev</span>
            @else
            <a href="{{ $services->previousPageUrl() }}" class="px-3 py-1.5 text-xs text-on-surface border border-outline-variant/30 rounded-lg hover:bg-white/5 transition-colors">← Prev</a>
            @endif
            @if($services->hasMorePages())
            <a href="{{ $services->nextPageUrl() }}" class="px-3 py-1.5 text-xs text-on-surface border border-outline-variant/30 rounded-lg hover:bg-white/5 transition-colors">Next →</a>
            @else
            <span class="px-3 py-1.5 text-xs text-outline border border-outline-variant/30 rounded-lg opacity-40">Next →</span>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
function filterServices(){
    const q=document.getElementById('svc-search').value.toLowerCase();
    document.querySelectorAll('.svc-row').forEach(r=>{
        r.style.display=r.dataset.name.includes(q)?'':'none';
    });
}
function filterCat(catId){
    document.querySelectorAll('.cat-btn').forEach(b=>{
        b.classList.toggle('active',b.dataset.cat===catId);
        b.classList.toggle('text-on-surface',b.dataset.cat===catId);
        b.classList.toggle('bg-white/5',b.dataset.cat===catId);
        b.classList.toggle('text-outline',b.dataset.cat!==catId);
    });
    document.querySelectorAll('.svc-row').forEach(r=>{
        r.style.display=(!catId||r.dataset.cat===catId)?'':'none';
    });
}
</script>
@endsection
