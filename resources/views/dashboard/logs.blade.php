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

        <button type="button" onclick="openModal('modal-adjust')" class="k-btn ml-auto" style="font-size:13px; padding:7px 16px;">
            Manual Adjustment
        </button>

    </div>
</form>

{{-- Results count --}}
<div class="text-xs mb-3" style="color: rgba(26,26,26,0.45);">
    {{ $logs->total() }} event{{ $logs->total() !== 1 ? 's' : '' }}
</div>

{{-- Events Table --}}
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
                <tr @style(['border-bottom: 1px solid rgba(26,26,26,0.12)', 'background: oklch(from var(--color-accent) l c h / 6%)' => $log->is_flagged])>
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
    <div class="flex items-center justify-between text-sm mb-8">
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

{{-- Sessions section --}}
<div class="flex items-center justify-between mb-3">
    <h2 class="font-display text-base">Punch Sessions</h2>
    <span class="text-xs" style="color:rgba(26,26,26,0.45);">Recent 50 — use filters above to narrow</span>
</div>

<div class="k-card overflow-hidden">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr style="border-bottom: 1.4px solid var(--color-ink);">
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Employee</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Site</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Clock in</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Clock out</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">State</th>
                <th class="text-right px-5 py-3 font-display" style="font-size:13px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sessions as $session)
                <tr @style(['border-bottom: 1px solid rgba(26,26,26,0.12)', 'opacity: 0.45' => $session->state === 'voided'])>
                    <td class="px-5 py-3 font-semibold">{{ $session->user_name }}</td>
                    <td class="px-5 py-3" style="color:rgba(26,26,26,0.65);">{{ $session->site_name }}</td>
                    <td class="px-5 py-3 font-mono" style="font-size:11px;">
                        {{ \Carbon\Carbon::parse($session->clocked_in_at)->format('d M H:i') }}
                    </td>
                    <td class="px-5 py-3 font-mono" style="font-size:11px; color:rgba(26,26,26,0.6);">
                        {{ $session->clocked_out_at ? \Carbon\Carbon::parse($session->clocked_out_at)->format('d M H:i') : '—' }}
                    </td>
                    <td class="px-5 py-3">
                        <span class="k-pill {{ $session->state === 'active' ? 'k-pill-accent' : 'k-pill-muted' }}">
                            {{ $session->state }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if ($session->state !== 'voided')
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    onclick="openAdjustModal(
                                        '{{ $session->id }}',
                                        '{{ addslashes($session->user_name) }}',
                                        '{{ $session->clocked_in_at ? \Carbon\Carbon::parse($session->clocked_in_at)->format('Y-m-d\TH:i') : '' }}',
                                        '{{ $session->clocked_out_at ? \Carbon\Carbon::parse($session->clocked_out_at)->format('Y-m-d\TH:i') : '' }}'
                                    )"
                                    class="k-btn" style="font-size:12px; padding:4px 10px;">
                                    Adjust
                                </button>
                                <button
                                    onclick="openVoidModal('{{ $session->id }}', '{{ addslashes($session->user_name) }}')"
                                    class="k-btn" style="font-size:12px; padding:4px 10px; color:rgba(26,26,26,0.5);">
                                    Void
                                </button>
                            </div>
                        @else
                            <span style="font-size:12px; color:rgba(26,26,26,0.35);">voided</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-sm" style="color: rgba(26,26,26,0.4);">
                        No sessions found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Modals ─────────────────────────────────────────────────────────────── --}}

{{-- Manual Adjustment --}}
<div id="modal-adjust" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:480px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-adjust')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-1">Manual Adjustment</h2>
        <p id="adjust-label" class="text-sm mb-5" style="color:rgba(26,26,26,0.55);"></p>
        <form id="form-adjust" method="POST" action="{{ route('dashboard.logs.adjust') }}" class="space-y-4">
            @csrf
            <input type="hidden" id="adjust-session-id" name="session_id" value="">
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Adjusted clock-in</label>
                    <input type="datetime-local" id="adjust-clock-in" name="adjusted_clock_in" class="k-input w-full">
                </div>
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Adjusted clock-out</label>
                    <input type="datetime-local" id="adjust-clock-out" name="adjusted_clock_out" class="k-input w-full">
                </div>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Note <span style="color:rgba(26,26,26,0.35);">(required)</span></label>
                <textarea name="note" required rows="3" class="k-input w-full" style="resize:vertical;"
                          placeholder="Reason for adjustment…"></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="k-btn" style="font-size:13px; padding:8px 20px;">Apply Adjustment</button>
                <button type="button" onclick="closeModal('modal-adjust')" class="k-btn" style="font-size:13px; padding:8px 16px; color:rgba(26,26,26,0.5);">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Void Session --}}
<div id="modal-void" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:400px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-void')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-2">Void Session</h2>
        <p id="void-label" class="text-sm mb-5" style="color:rgba(26,26,26,0.6);"></p>
        <form id="form-void" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex gap-3">
                <button type="submit" class="k-btn" style="font-size:13px; padding:8px 20px;">Void Session</button>
                <button type="button" onclick="closeModal('modal-void')" class="k-btn" style="font-size:13px; padding:8px 16px; color:rgba(26,26,26,0.5);">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openModal(id) {
        const m = document.getElementById(id);
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeModal(id) {
        const m = document.getElementById(id);
        m.classList.remove('flex');
        m.classList.add('hidden');
    }

    document.querySelectorAll('[id^="modal-"]').forEach(m => {
        m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
    });

    function openAdjustModal(sessionId, userName, clockIn, clockOut) {
        document.getElementById('adjust-session-id').value  = sessionId;
        document.getElementById('adjust-label').textContent = 'Adjusting session for ' + userName + '.';
        document.getElementById('adjust-clock-in').value    = clockIn;
        document.getElementById('adjust-clock-out').value   = clockOut;
        openModal('modal-adjust');
    }

    function openVoidModal(sessionId, userName) {
        document.getElementById('void-label').textContent = userName + '\'s session will be marked as voided. The audit record is preserved.';
        document.getElementById('form-void').action = '/dashboard/logs/sessions/' + sessionId;
        openModal('modal-void');
    }
</script>
@endpush
