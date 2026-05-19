@extends('dashboard.layout')

@section('title', 'Team')
@section('heading', 'Team')

@section('content')

{{-- Filters + Add button --}}
<div class="flex items-center gap-3 mb-6 flex-wrap">
    <form method="GET" action="{{ route('dashboard.team') }}" class="flex items-center gap-3 flex-wrap flex-1">
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

        <div class="flex items-center gap-2">
            <span class="text-xs" style="color: rgba(26,26,26,0.45);">Date</span>
            <input type="date" name="date" value="{{ $date }}"
                   class="k-input" style="width: 140px;"
                   onchange="this.form.submit()">
        </div>
    </form>

    <button onclick="openModal('modal-create')" class="k-btn" style="font-size:13px; padding:7px 16px; white-space:nowrap;">
        + Add Employee
    </button>
</div>

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
                <th class="text-right px-5 py-3 font-display" style="font-size:13px;">Actions</th>
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
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                onclick="openEditModal('{{ $employee->id }}', '{{ addslashes($employee->name) }}', '{{ addslashes($employee->email) }}', '{{ $employee->role }}')"
                                class="k-btn" style="font-size:12px; padding:4px 10px;">
                                Edit
                            </button>
                            <button
                                onclick="openResetPinModal('{{ $employee->id }}', '{{ addslashes($employee->name) }}')"
                                class="k-btn" style="font-size:12px; padding:4px 10px;">
                                Reset PIN
                            </button>
                            <button
                                onclick="openDeactivateModal('{{ $employee->id }}', '{{ addslashes($employee->name) }}')"
                                class="k-btn" style="font-size:12px; padding:4px 10px; color: rgba(26,26,26,0.5);">
                                Deactivate
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-sm" style="color: rgba(26,26,26,0.4);">
                        No employees match the current filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Modals ─────────────────────────────────────────────────────────────── --}}

{{-- Create Employee --}}
<div id="modal-create" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:480px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-create')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-5">Add Employee</h2>
        <form method="POST" action="{{ route('dashboard.team.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Full name</label>
                <input type="text" name="full_name" required class="k-input w-full" value="{{ old('full_name') }}">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Email</label>
                <input type="email" name="email" required class="k-input w-full" value="{{ old('email') }}">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Password</label>
                <input type="password" name="password" required minlength="8" class="k-input w-full">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Role</label>
                <select name="role" class="k-input w-full" style="cursor:pointer;">
                    <option value="employee">Employee</option>
                    <option value="manager">Manager</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="k-btn" style="font-size:13px; padding:8px 20px;">Add Employee</button>
                <button type="button" onclick="closeModal('modal-create')" class="k-btn" style="font-size:13px; padding:8px 16px; color:rgba(26,26,26,0.5);">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Employee --}}
<div id="modal-edit" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:480px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-edit')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-5">Edit Employee</h2>
        <form id="form-edit" method="POST" action="" class="space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Full name</label>
                <input type="text" id="edit-full-name" name="full_name" required class="k-input w-full">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Email</label>
                <input type="email" id="edit-email" name="email" required class="k-input w-full">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Role</label>
                <select id="edit-role" name="role" class="k-input w-full" style="cursor:pointer;">
                    <option value="employee">Employee</option>
                    <option value="manager">Manager</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="k-btn" style="font-size:13px; padding:8px 20px;">Save Changes</button>
                <button type="button" onclick="closeModal('modal-edit')" class="k-btn" style="font-size:13px; padding:8px 16px; color:rgba(26,26,26,0.5);">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Reset PIN --}}
<div id="modal-reset-pin" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:400px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-reset-pin')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-2">Reset PIN</h2>
        <p id="reset-pin-label" class="text-sm mb-5" style="color:rgba(26,26,26,0.6);"></p>
        <form id="form-reset-pin" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">New 4-digit PIN</label>
                <input type="text" name="new_pin" required pattern="\d{4}" maxlength="4" inputmode="numeric"
                       class="k-input w-full font-mono" placeholder="0000">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="k-btn" style="font-size:13px; padding:8px 20px;">Reset PIN</button>
                <button type="button" onclick="closeModal('modal-reset-pin')" class="k-btn" style="font-size:13px; padding:8px 16px; color:rgba(26,26,26,0.5);">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Deactivate --}}
<div id="modal-deactivate" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:400px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-deactivate')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-2">Deactivate Employee</h2>
        <p id="deactivate-label" class="text-sm mb-5" style="color:rgba(26,26,26,0.6);"></p>
        <form id="form-deactivate" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex gap-3">
                <button type="submit" class="k-btn" style="font-size:13px; padding:8px 20px;">Deactivate</button>
                <button type="button" onclick="closeModal('modal-deactivate')" class="k-btn" style="font-size:13px; padding:8px 16px; color:rgba(26,26,26,0.5);">Cancel</button>
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

    function openEditModal(id, name, email, role) {
        document.getElementById('edit-full-name').value = name;
        document.getElementById('edit-email').value = email;
        document.getElementById('edit-role').value = role;
        document.getElementById('form-edit').action = '/dashboard/team/' + id;
        openModal('modal-edit');
    }

    function openResetPinModal(id, name) {
        document.getElementById('reset-pin-label').textContent = 'Set a new PIN for ' + name + '.';
        document.getElementById('form-reset-pin').action = '/dashboard/team/' + id + '/reset-pin';
        openModal('modal-reset-pin');
    }

    function openDeactivateModal(id, name) {
        document.getElementById('deactivate-label').textContent =
            name + ' will be marked inactive and will no longer be able to log in.';
        document.getElementById('form-deactivate').action = '/dashboard/team/' + id;
        openModal('modal-deactivate');
    }

    @if ($errors->any())
        openModal('modal-create');
    @endif

    setTimeout(() => location.reload(), 30000);
</script>
@endpush
