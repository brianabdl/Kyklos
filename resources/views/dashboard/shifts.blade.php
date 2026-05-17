@extends('dashboard.layout')

@section('title', 'Shifts')
@section('heading', 'Shifts')

@section('content')

{{-- Week navigation --}}
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('dashboard.shifts', ['week' => $prevWeek]) }}" class="k-btn" style="font-size:13px; padding:6px 12px;">← Prev</a>

    <div class="font-display text-lg">
        {{ $start->format('d M') }} – {{ $end->format('d M Y') }}
    </div>

    <a href="{{ route('dashboard.shifts', ['week' => $nextWeek]) }}" class="k-btn" style="font-size:13px; padding:6px 12px;">Next →</a>

    <a href="{{ route('dashboard.shifts', ['week' => now()->startOfWeek()->toDateString()]) }}"
       class="text-sm ml-2" style="color: rgba(26,26,26,0.45);">
        This week
    </a>
</div>

{{-- Schedule grid --}}
<div class="k-card overflow-x-auto">
    <table class="w-full text-sm border-collapse" style="min-width: 640px;">
        <thead>
            <tr style="border-bottom: 1.4px solid var(--color-ink);">
                <th class="text-left px-5 py-3 font-display" style="font-size:13px; min-width:140px;">Employee</th>
                @foreach ($days as $i => $day)
                    <th class="text-center px-3 py-3 font-display" style="font-size:13px;">
                        <div>{{ $day->format('D') }}</div>
                        <div class="font-mono font-normal text-xs" style="color: rgba(26,26,26,0.5);">
                            {{ $day->format('d') }}
                        </div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $employee)
                <tr style="border-bottom: 1px solid rgba(26,26,26,0.12);">
                    <td class="px-5 py-3 font-semibold whitespace-nowrap">{{ $employee->full_name }}</td>
                    @foreach ($days as $i => $day)
                        @php $shift = $schedule[$employee->id][$i] ?? null; @endphp
                        <td class="px-3 py-3 text-center">
                            @if ($shift)
                                <div class="inline-block k-card px-2 py-1 text-xs" style="border-radius:6px; min-width:80px;">
                                    <div class="font-mono" style="font-size:10px;">
                                        {{ \Carbon\Carbon::parse($shift->scheduled_start)->format('H:i') }}
                                        –
                                        {{ \Carbon\Carbon::parse($shift->scheduled_end)->format('H:i') }}
                                    </div>
                                    @if ($shift->site)
                                        <div class="mt-0.5" style="color: rgba(26,26,26,0.5); font-size:10px;">
                                            {{ $shift->site->name }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span style="color: rgba(26,26,26,0.25);">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 1 + count($days) }}" class="px-5 py-8 text-center text-sm" style="color: rgba(26,26,26,0.4);">
                        No employees found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
