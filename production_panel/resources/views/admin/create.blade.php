@extends('layouts.app')
@section('title', 'Add Provider')
@section('page-title', 'Add API Provider')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.providers.index') }}" class="text-slate-400 hover:text-white transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-xl font-bold text-white">Add API Provider</h2>
            <p class="text-sm text-slate-500">Connect a new SMM panel provider</p>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-6">
        <form method="POST" action="{{ route('admin.providers.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-label-caps text-outline uppercase tracking-widest mb-2">Provider Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="w-full bg-surface-container border border-outline-variant/40 rounded-xl px-4 py-3 text-on-surface text-sm focus:outline-none focus:border-primary transition-colors"
                    placeholder="e.g. SMMKing" required>
                @error('name') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-label-caps text-outline uppercase tracking-widest mb-2">API URL</label>
                <input type="url" name="url" value="{{ old('url') }}"
                    class="w-full bg-surface-container border border-outline-variant/40 rounded-xl px-4 py-3 text-on-surface text-sm focus:outline-none focus:border-primary transition-colors"
                    placeholder="https://provider.com/api/v2" required>
                @error('url') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-label-caps text-outline uppercase tracking-widest mb-2">API Key</label>
                <input type="text" name="api_key" value="{{ old('api_key') }}"
                    class="w-full bg-surface-container border border-outline-variant/40 rounded-xl px-4 py-3 text-on-surface text-sm focus:outline-none focus:border-primary transition-colors font-mono"
                    placeholder="Your API key from the provider dashboard" required>
                @error('api_key') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-label-caps text-outline uppercase tracking-widest mb-2">Markup % <span class="text-slate-500 normal-case">(your profit margin)</span></label>
                <div class="relative">
                    <input type="number" name="percentage_increase" value="{{ old('percentage_increase', 30) }}"
                        min="0" max="10000" step="0.01"
                        class="w-full bg-surface-container border border-outline-variant/40 rounded-xl px-4 py-3 text-on-surface text-sm focus:outline-none focus:border-primary transition-colors pr-10"
                        required>
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-outline">%</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">A 30% markup means if provider charges $1.00, you charge $1.30</p>
                @error('percentage_increase') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-gradient-primary text-white font-semibold py-3 rounded-xl hover:brightness-110 transition-all text-sm">
                    Add Provider
                </button>
                <a href="{{ route('admin.providers.index') }}"
                    class="px-6 py-3 rounded-xl border border-outline-variant/40 text-slate-400 hover:text-white hover:border-white/20 transition-all text-sm text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
