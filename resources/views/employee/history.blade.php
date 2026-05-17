@extends('employee.layout')

@section('title', 'History')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div class="font-display text-3xl">Punch History</div>

    {{-- Month filter --}}
    <form method="GET" action="{{ route('employee.history') }}">
        <select name="month" onchange="this.form.submit()" class="k-input" style="width: auto; padding: 6px 12px; font-size: 13px;">
            @foreach ($monthOptions as $opt)
                <option value="{{ $opt }}" {{ $opt === $month ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $opt)->format('F Y') }}
                </option>
            @endforeach
        </select>
    </form>
</div>

@if ($sessions->isEmpty())
    <div class="k-card px-5 py-8 text-center text-sm" style="color: rgba(26,26,26,0.45);">
        No sessions recorded for {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}.
    </div>
@else
    <div class="k-card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr style="border-bottom: 1px solid var(--color-ink);">
                    <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Date</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Site</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">In</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Out</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Work</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Breaks</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sessions as $session)
                @php
                    $wh = intdiv($session->work_seconds, 3600);
                    $wm = intdiv($session->work_seconds % 3600, 60);
                    $bm = intdiv($session->break_seconds, 60);
                @endphp
                <tr class="{{ $session->is_flagged ? 'flagged-row' : '' }}"
                    style="border-bottom: 1px solid rgba(26,26,26,0.08); {{ $session->is_flagged ? 'background: rgba(255,200,0,0.07);' : '' }}">
                    <td class="px-4 py-3 font-mono text-xs">{{ $session->clocked_in_at->format('d M') }}</td>
                    <td class="px-4 py-3">{{ $session->site->name }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $session->clocked_in_at->format('H:i') }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $session->clocked_out_at?->format('H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $wh }}h {{ $wm }}m</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $bm > 0 ? $bm . 'm' : '—' }}</td>
                    <td class="px-4 py-3">
                        @if ($session->is_flagged)
                            <span class="k-pill k-pill-accent text-xs">flagged</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($sessions->hasPages())
        <div class="mt-4 flex gap-2 text-sm">
            @if ($sessions->onFirstPage())
                <span class="k-btn opacity-40" style="padding: 5px 12px;">← Prev</span>
            @else
                <a href="{{ $sessions->previousPageUrl() }}" class="k-btn" style="padding: 5px 12px;">← Prev</a>
            @endif

            <span class="px-3 py-1 text-xs" style="color: rgba(26,26,26,0.5); line-height: 2;">
                Page {{ $sessions->currentPage() }} of {{ $sessions->lastPage() }}
            </span>

            @if ($sessions->hasMorePages())
                <a href="{{ $sessions->nextPageUrl() }}" class="k-btn" style="padding: 5px 12px;">Next →</a>
            @else
                <span class="k-btn opacity-40" style="padding: 5px 12px;">Next →</span>
            @endif
        </div>
    @endif
@endif

@endsection
