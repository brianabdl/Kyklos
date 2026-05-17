@extends('dashboard.layout')

@section('title', 'Team')
@section('heading', 'Team')

@section('content')

{{-- Filters --}}
<form method="GET" action="{{ route('dashboard.team') }}" class="flex items-center gap-3 mb-6 flex-wrap">
    <input
        type="text"
        name="search"
        value="{{ $search }}"
        placeholder="Search employee…"
        class="k-input"
        style="width: 200px;"
    >

    <select name="status" class="k-input" style="width: 140px; cursor: pointer;">
        <option value="">All statuses</option>
        <option value="in"    {{ $status === 'in'    ? 'selected' : '' }}>On clock</option>
        <option value="break" {{ $status === 'break' ? 'selected' : '' }}>On break</option>
        <option value="out"   {{ $status === 'out'   ? 'selected' : '' }}>Out</option>
    </select>

    <input type="hidden" name="date" value="{{ $date }}">

    <button type="submit" class="k-btn" style="font-size: 13px; padding: 7px 14px;">Filter</button>

    @if ($search || $status)
        <a href="{{ route('dashboard.team') }}" class="text-sm" style="color: rgba(26,26,26,0.45);">Clear</a>
    @endif

    <div class="ml-auto flex items-center gap-2">
        <span class="text-xs" style="color: rgba(26,26,26,0.45);">Date</span>
        <input type="date" name="date" value="{{ $date }}"
               class="k-input" style="width: 140px;"
               onchange="this.form.submit()">
    </div>
</form>

{{-- Employee count --}}
<div class="text-xs mb-4" style="color: rgba(26,26,26,0.45);">
    {{ $employees->count() }} employee{{ $employees->count() !== 1 ? 's' : '' }}
</div>

{{-- Table --}}
<div class="k-card overflow-hidden">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr style="border-bottom: 1.4px solid var(--color-ink);">
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Employee</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Status</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Site</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Clocked in</th>
                <th class="text-right px-5 py-3 font-display" style="font-size:13px;">Hours today</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $employee)
                <tr style="border-bottom: 1px solid rgba(26,26,26,0.12);">
                    <td class="px-5 py-3 font-semibold">{{ $employee->name }}</td>
                    <td class="px-5 py-3">
                        @php
                            $pillClass = match($employee->status) {
                                'in'    => 'k-pill-accent',
                                'break' => 'k-pill',
                                default => 'k-pill-muted',
                            };
                            $label = match($employee->status) {
                                'in'    => 'on clock',
                                'break' => 'on break',
                                default => 'out',
                            };
                        @endphp
                        <span class="k-pill {{ $pillClass }}">{{ $label }}</span>
                    </td>
                    <td class="px-5 py-3" style="color: rgba(26,26,26,0.65);">
                        {{ $employee->site ?? '—' }}
                    </td>
                    <td class="px-5 py-3 font-mono text-xs">
                        {{ $employee->clockedInAt ? $employee->clockedInAt->format('H:i') : '—' }}
                    </td>
                    <td class="px-5 py-3 text-right font-mono text-xs">
                        {{ $employee->hoursToday ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-sm" style="color: rgba(26,26,26,0.4);">
                        No employees match the current filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@push('scripts')
<script>
    setTimeout(() => location.reload(), 30000);
</script>
@endpush
