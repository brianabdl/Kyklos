@extends('dashboard.layout')

@section('title', 'Shifts')
@section('heading', 'Shifts')

@section('content')

{{-- Week navigation + Add Shift --}}
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

    <button onclick="openCreateModal()" class="k-btn ml-auto" style="font-size:13px; padding:7px 16px;">
        + Add Shift
    </button>
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
                                <div class="inline-block k-card px-2 py-1 text-xs" style="border-radius:6px; min-width:80px; position:relative; group;">
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
                                    <div class="flex justify-center gap-1 mt-1">
                                        <button
                                            onclick="openEditModal(
                                                '{{ $shift->id }}',
                                                '{{ $shift->user_id }}',
                                                '{{ $shift->site_id }}',
                                                '{{ \Carbon\Carbon::parse($shift->scheduled_start)->format('Y-m-d\TH:i') }}',
                                                '{{ \Carbon\Carbon::parse($shift->scheduled_end)->format('Y-m-d\TH:i') }}',
                                                '{{ addslashes($shift->label ?? '') }}'
                                            )"
                                            style="font-size:10px; color:rgba(26,26,26,0.5); background:none; border:none; cursor:pointer; padding:0 2px;">
                                            edit
                                        </button>
                                        <span style="color:rgba(26,26,26,0.25);">·</span>
                                        <button
                                            onclick="openDeleteModal('{{ $shift->id }}')"
                                            style="font-size:10px; color:rgba(26,26,26,0.5); background:none; border:none; cursor:pointer; padding:0 2px;">
                                            del
                                        </button>
                                    </div>
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

{{-- ── Modals ─────────────────────────────────────────────────────────────── --}}

{{-- Create Shift --}}
<div id="modal-create" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:480px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-create')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-5">Add Shift</h2>
        <form method="POST" action="{{ route('dashboard.shifts.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="week" value="{{ $week }}">
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Employee</label>
                <select name="user_id" required class="k-input w-full" style="cursor:pointer;">
                    <option value="">Select employee…</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Site</label>
                <select name="site_id" required class="k-input w-full" style="cursor:pointer;">
                    <option value="">Select site…</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Start</label>
                    <input type="datetime-local" name="scheduled_start" required class="k-input w-full">
                </div>
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">End</label>
                    <input type="datetime-local" name="scheduled_end" required class="k-input w-full">
                </div>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Label (optional)</label>
                <input type="text" name="label" class="k-input w-full" placeholder="e.g. Morning shift">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="k-btn" style="font-size:13px; padding:8px 20px;">Create Shift</button>
                <button type="button" onclick="closeModal('modal-create')" class="k-btn" style="font-size:13px; padding:8px 16px; color:rgba(26,26,26,0.5);">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Shift --}}
<div id="modal-edit" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:480px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-edit')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-5">Edit Shift</h2>
        <form id="form-edit" method="POST" action="" class="space-y-4">
            @csrf
            @method('PATCH')
            <input type="hidden" name="week" value="{{ $week }}">
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Employee</label>
                <select id="edit-user-id" name="user_id" required class="k-input w-full" style="cursor:pointer;">
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Site</label>
                <select id="edit-site-id" name="site_id" required class="k-input w-full" style="cursor:pointer;">
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Start</label>
                    <input type="datetime-local" id="edit-start" name="scheduled_start" required class="k-input w-full">
                </div>
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">End</label>
                    <input type="datetime-local" id="edit-end" name="scheduled_end" required class="k-input w-full">
                </div>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Label (optional)</label>
                <input type="text" id="edit-label" name="label" class="k-input w-full">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="k-btn" style="font-size:13px; padding:8px 20px;">Save Changes</button>
                <button type="button" onclick="closeModal('modal-edit')" class="k-btn" style="font-size:13px; padding:8px 16px; color:rgba(26,26,26,0.5);">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete Shift --}}
<div id="modal-delete" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:380px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-delete')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-2">Delete Shift</h2>
        <p class="text-sm mb-5" style="color:rgba(26,26,26,0.6);">This shift will be permanently removed.</p>
        <form id="form-delete" method="POST" action="">
            @csrf
            @method('DELETE')
            <input type="hidden" name="week" value="{{ $week }}">
            <div class="flex gap-3">
                <button type="submit" class="k-btn" style="font-size:13px; padding:8px 20px;">Delete</button>
                <button type="button" onclick="closeModal('modal-delete')" class="k-btn" style="font-size:13px; padding:8px 16px; color:rgba(26,26,26,0.5);">Cancel</button>
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

    function openCreateModal() {
        openModal('modal-create');
    }

    function openEditModal(id, userId, siteId, start, end, label) {
        document.getElementById('edit-user-id').value = userId;
        document.getElementById('edit-site-id').value = siteId;
        document.getElementById('edit-start').value   = start;
        document.getElementById('edit-end').value     = end;
        document.getElementById('edit-label').value   = label;
        document.getElementById('form-edit').action   = '/dashboard/shifts/' + id;
        openModal('modal-edit');
    }

    function openDeleteModal(id) {
        document.getElementById('form-delete').action = '/dashboard/shifts/' + id;
        openModal('modal-delete');
    }

    @if ($errors->any())
        openModal('modal-create');
    @endif
</script>
@endpush
