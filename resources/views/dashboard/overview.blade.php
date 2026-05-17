@extends('dashboard.layout')

@section('title', 'Overview')
@section('heading', 'Overview')

@section('content')

{{-- KPI Cards --}}
<div class="grid grid-cols-4 gap-4 mb-6">

    @php
        $kpis = [
            ['label' => 'Total employees', 'value' => $totalEmployees, 'accent' => false],
            ['label' => 'On the clock',    'value' => $onTheClock,    'accent' => $onTheClock > 0],
            ['label' => 'Late today',      'value' => $lateCount,     'accent' => $lateCount > 0],
            ['label' => 'No-shows',        'value' => $noShowCount,   'accent' => $noShowCount > 0],
        ];
    @endphp

    @foreach ($kpis as $kpi)
        <div class="{{ $kpi['accent'] ? 'k-card-accent' : 'k-card' }} px-5 py-4">
            <div class="font-mono text-4xl mb-1" style="letter-spacing: -0.03em; color: {{ $kpi['accent'] ? 'var(--color-accent)' : 'var(--color-ink)' }}">
                {{ $kpi['value'] }}
            </div>
            <div class="text-xs" style="color: rgba(26,26,26,0.55);">{{ $kpi['label'] }}</div>
        </div>
    @endforeach

</div>

<div class="grid grid-cols-3 gap-6">

    {{-- Needs Attention --}}
    <div class="col-span-1">
        <div class="text-sm font-display mb-3" style="color: rgba(26,26,26,0.7);">Needs attention</div>

        @if ($needsAttention->isEmpty())
            <div class="k-card px-5 py-4 text-sm" style="color: rgba(26,26,26,0.45);">
                All clear today.
            </div>
        @else
            <div class="flex flex-col gap-2">
                @foreach ($needsAttention as $item)
                    <div class="k-card-accent px-4 py-3 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-sm">{{ $item['name'] }}</div>
                            <div class="text-xs mt-0.5" style="color: rgba(26,26,26,0.55);">{{ $item['issue'] }}</div>
                        </div>
                        <span class="k-pill k-pill-accent text-xs">follow up</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Weekly Hours Chart --}}
    <div class="col-span-2">
        <div class="text-sm font-display mb-3" style="color: rgba(26,26,26,0.7);">
            Hours worked this week
            <span class="font-sans text-xs ml-2" style="color: rgba(26,26,26,0.4);">
                {{ $weekStart->format('M d') }} – {{ $weekEnd->format('M d') }}
            </span>
        </div>
        <div class="k-card px-5 py-4">
            {{-- Bar chart --}}
            <div class="flex items-end gap-3" style="height: 120px;">
                @foreach ($hoursByDay as $i => $day)
                    @php
                        $heightPct = $maxHours > 0 ? ($day['hours'] / $maxHours * 100) : 0;
                        $isToday   = $i === (int) now()->dayOfWeekIso - 1;
                    @endphp
                    <div class="flex-1 flex flex-col items-center justify-end gap-1 h-full">
                        <div class="font-mono text-xs" style="font-size:10px; color: rgba(26,26,26,0.45);">
                            {{ $day['hours'] > 0 ? $day['hours'].'h' : '' }}
                        </div>
                        <div class="w-full relative" style="height: {{ max(3, $heightPct) }}%; background-color: {{ $isToday ? 'var(--color-accent)' : 'rgba(217,119,87,0.25)' }}; border: 1.2px solid {{ $isToday ? 'var(--color-accent)' : 'rgba(26,26,26,0.2)' }}; border-radius: 3px 3px 0 0;"></div>
                        <div class="font-mono text-xs font-semibold" style="font-size:10px; color: {{ $isToday ? 'var(--color-accent)' : 'rgba(26,26,26,0.55)' }};">
                            {{ $day['day'] }}
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3 pt-3" style="border-top: 1px solid rgba(26,26,26,0.15);">
                <span class="text-xs" style="color: rgba(26,26,26,0.45);">
                    {{ $overtimeHoursWeek }}h overtime this week
                </span>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    // Auto-refresh every 30 seconds
    setTimeout(() => location.reload(), 30000);
</script>
@endpush
