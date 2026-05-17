@extends('dashboard.layout')

@section('title', 'Logs')
@section('heading', 'Logs')

@section('content')

{{-- Filters --}}
<form method="GET" action="{{ route('dashboard.logs') }}" class="k-card px-5 py-4 mb-6">
    <div class="flex flex-wrap gap-4 items-end">

        <div>
            <label class="block text-xs mb-1" style="color: rgba(26,26,26,0.55);">From</label>
            <input type="date" name="from" value="{{ $from }}" class="k-input" style="width:140px;">
        </div>

        <div>
            <label class="block text-xs mb-1" style="color: rgba(26,26,26,0.55);">To</label>
            <input type="date" name="to" value="{{ $to }}" class="k-input" style="width:140px;">
        </div>

        <div>
            <label class="block text-xs mb-1" style="color: rgba(26,26,26,0.55);">Event type</label>
            <select name="event_type" class="k-input" style="width:150px; cursor:pointer;">
                <option value="">All events</option>
                @foreach ($eventTypes as $type)
                    <option value="{{ $type }}" {{ $eventType === $type ? 'selected' : '' }}>
                        {{ str_replace('_', ' ', $type) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs mb-1" style="color: rgba(26,26,26,0.55);">Employee</label>
            <select name="user" class="k-input" style="width:160px; cursor:pointer;">
                <option value="">All employees</option>
                @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $userId === $emp->id ? 'selected' : '' }}>
                        {{ $emp->full_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs mb-1" style="color: rgba(26,26,26,0.55);">Site</label>
            <select name="site" class="k-input" style="width:150px; cursor:pointer;">
                <option value="">All sites</option>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}" {{ $siteId === $site->id ? 'selected' : '' }}>
                        {{ $site->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="k-btn" style="font-size:13px; padding:7px 14px;">Apply</button>
            @if ($from || $to || $eventType || $userId || $siteId)
                <a href="{{ route('dashboard.logs') }}" class="k-btn" style="font-size:13px; padding:7px 14px; color: rgba(26,26,26,0.5);">Clear</a>
            @endif
        </div>

    </div>
</form>

{{-- Results count --}}
<div class="text-xs mb-3" style="color: rgba(26,26,26,0.45);">
    {{ $logs->total() }} event{{ $logs->total() !== 1 ? 's' : '' }}
</div>

{{-- Table --}}
<div class="k-card overflow-hidden mb-4">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr style="border-bottom: 1.4px solid var(--color-ink);">
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Time</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Employee</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Event</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Site</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Method</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Flag</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr style="border-bottom: 1px solid rgba(26,26,26,0.12); {{ $log->is_flagged ? 'background: oklch(from var(--color-accent) l c h / 6%);' : '' }}">
                    <td class="px-5 py-3 font-mono" style="font-size:11px; white-space:nowrap; color: rgba(26,26,26,0.7);">
                        {{ \Carbon\Carbon::parse($log->occurred_at)->format('d M H:i') }}
                    </td>
                    <td class="px-5 py-3 font-semibold">{{ $log->user_name }}</td>
                    <td class="px-5 py-3">
                        <span class="k-pill {{ in_array($log->event_type, ['clock_in','clock_out']) ? 'k-pill-accent' : 'k-pill-muted' }}">
                            {{ str_replace('_', '-', $log->event_type) }}
                        </span>
                    </td>
                    <td class="px-5 py-3" style="color: rgba(26,26,26,0.65);">{{ $log->site_name }}</td>
                    <td class="px-5 py-3 font-mono text-xs" style="color: rgba(26,26,26,0.55);">{{ $log->method ?? '—' }}</td>
                    <td class="px-5 py-3">
                        @if ($log->is_flagged)
                            <span class="k-pill k-pill-accent" title="{{ $log->flag_reason }}">flagged</span>
                        @else
                            <span style="color: rgba(26,26,26,0.25);">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-sm" style="color: rgba(26,26,26,0.4);">
                        No events match the current filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if ($logs->hasPages())
    <div class="flex items-center justify-between text-sm">
        <div style="color: rgba(26,26,26,0.45);">
            Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}
        </div>
        <div class="flex gap-2">
            @if ($logs->onFirstPage())
                <span class="k-btn" style="opacity:0.35; cursor:not-allowed; font-size:13px; padding:6px 12px;">← Prev</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="k-btn" style="font-size:13px; padding:6px 12px;">← Prev</a>
            @endif

            @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="k-btn" style="font-size:13px; padding:6px 12px;">Next →</a>
            @else
                <span class="k-btn" style="opacity:0.35; cursor:not-allowed; font-size:13px; padding:6px 12px;">Next →</span>
            @endif
        </div>
    </div>
@endif

@endsection
