@extends('employee.layout')

@section('title', 'Shifts')

@section('content')

<div class="font-display text-3xl mb-6">My Shifts</div>

{{-- Upcoming --}}
<div class="mb-8">
    <div class="text-xs mb-3 font-semibold" style="color: rgba(26,26,26,0.5); text-transform: uppercase; letter-spacing: 0.06em;">
        Upcoming
    </div>

    @if ($upcoming->isEmpty())
        <div class="k-card px-5 py-4 text-sm" style="color: rgba(26,26,26,0.45);">
            No upcoming shifts scheduled.
        </div>
    @else
        <div class="k-card overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid var(--color-ink);">
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Site</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Start</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">End</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Duration</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Label</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($upcoming as $shift)
                    @php
                        $durationMins = $shift->scheduled_start->diffInMinutes($shift->scheduled_end);
                        $dh = intdiv($durationMins, 60);
                        $dm = $durationMins % 60;
                        $isToday = $shift->scheduled_start->isToday();
                    @endphp
                    <tr style="border-bottom: 1px solid rgba(26,26,26,0.08); {{ $isToday ? 'background: rgba(26,26,26,0.03);' : '' }}">
                        <td class="px-4 py-3 font-mono text-xs">
                            {{ $shift->scheduled_start->format('d M') }}
                            @if ($isToday)
                                <span class="k-pill text-xs ml-1" style="background: var(--color-ink); color: var(--color-paper);">today</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $shift->site->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $shift->scheduled_start->format('H:i') }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $shift->scheduled_end->format('H:i') }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $dh }}h {{ $dm > 0 ? $dm . 'm' : '' }}</td>
                        <td class="px-4 py-3 text-xs" style="color: rgba(26,26,26,0.55);">{{ $shift->label ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Recent --}}
<div>
    <div class="text-xs mb-3 font-semibold" style="color: rgba(26,26,26,0.5); text-transform: uppercase; letter-spacing: 0.06em;">
        Recent
    </div>

    @if ($past->isEmpty())
        <div class="k-card px-5 py-4 text-sm" style="color: rgba(26,26,26,0.45);">
            No past shifts on record.
        </div>
    @else
        <div class="k-card overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid var(--color-ink);">
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Site</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Start</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">End</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Duration</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color: rgba(26,26,26,0.5);">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($past as $shift)
                    @php
                        $durationMins = $shift->scheduled_start->diffInMinutes($shift->scheduled_end);
                        $dh = intdiv($durationMins, 60);
                        $dm = $durationMins % 60;
                    @endphp
                    <tr style="border-bottom: 1px solid rgba(26,26,26,0.08);">
                        <td class="px-4 py-3 font-mono text-xs">{{ $shift->scheduled_start->format('d M') }}</td>
                        <td class="px-4 py-3">{{ $shift->site->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $shift->scheduled_start->format('H:i') }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $shift->scheduled_end->format('H:i') }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $dh }}h {{ $dm > 0 ? $dm . 'm' : '' }}</td>
                        <td class="px-4 py-3">
                            @if ($shift->status === 'completed')
                                <span class="k-pill text-xs" style="background: #dcfce7; color: #15803d;">completed</span>
                            @else
                                <span class="k-pill k-pill-accent text-xs">missed</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
