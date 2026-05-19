@extends('dashboard.layout')

@section('title', 'Sites')
@section('heading', 'Sites')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div class="text-xs" style="color:rgba(26,26,26,0.45);">
        {{ $sites->count() }} site{{ $sites->count() !== 1 ? 's' : '' }}
    </div>
    <button onclick="openModal('modal-create')" class="k-btn" style="font-size:13px; padding:7px 16px;">
        + Add Site
    </button>
</div>

{{-- Table --}}
<div class="k-card overflow-hidden">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr style="border-bottom: 1.4px solid var(--color-ink);">
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Name</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Address</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Coordinates</th>
                <th class="text-left px-5 py-3 font-display" style="font-size:13px;">Geofence</th>
                <th class="text-right px-5 py-3 font-display" style="font-size:13px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sites as $site)
                <tr style="border-bottom: 1px solid rgba(26,26,26,0.12);">
                    <td class="px-5 py-3 font-semibold">{{ $site->name }}</td>
                    <td class="px-5 py-3" style="color:rgba(26,26,26,0.65);">{{ $site->address ?? '—' }}</td>
                    <td class="px-5 py-3 font-mono" style="font-size:11px; color:rgba(26,26,26,0.55);">
                        {{ number_format($site->lat, 5) }}, {{ number_format($site->lng, 5) }}
                    </td>
                    <td class="px-5 py-3 font-mono" style="font-size:12px;">
                        {{ $site->geofence_radius_m ? $site->geofence_radius_m . ' m' : '—' }}
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                onclick="openEditModal(
                                    '{{ $site->id }}',
                                    '{{ addslashes($site->name) }}',
                                    '{{ addslashes($site->address ?? '') }}',
                                    '{{ $site->lat }}',
                                    '{{ $site->lng }}',
                                    '{{ $site->geofence_radius_m ?? '' }}'
                                )"
                                class="k-btn" style="font-size:12px; padding:4px 10px;">
                                Edit
                            </button>
                            <button
                                onclick="openDeleteModal('{{ $site->id }}', '{{ addslashes($site->name) }}')"
                                class="k-btn" style="font-size:12px; padding:4px 10px; color:rgba(26,26,26,0.5);">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-sm" style="color:rgba(26,26,26,0.4);">
                        No sites yet. Add your first site to enable GPS clock-ins.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Modals ─────────────────────────────────────────────────────────────── --}}

{{-- Create Site --}}
<div id="modal-create" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:520px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-create')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-5">Add Site</h2>
        <form method="POST" action="{{ route('dashboard.sites.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Site name</label>
                <input type="text" name="name" required class="k-input w-full" value="{{ old('name') }}" placeholder="e.g. Main Office">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Address (optional)</label>
                <input type="text" name="address" class="k-input w-full" value="{{ old('address') }}" placeholder="Street, city…">
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Latitude</label>
                    <input type="number" name="lat" step="0.000001" required class="k-input w-full font-mono" value="{{ old('lat') }}" placeholder="-6.200000">
                </div>
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Longitude</label>
                    <input type="number" name="lng" step="0.000001" required class="k-input w-full font-mono" value="{{ old('lng') }}" placeholder="106.816666">
                </div>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Geofence radius (metres, optional)</label>
                <input type="number" name="geofence_radius_m" min="10" max="50000" class="k-input w-full" value="{{ old('geofence_radius_m') }}" placeholder="100">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="k-btn" style="font-size:13px; padding:8px 20px;">Add Site</button>
                <button type="button" onclick="closeModal('modal-create')" class="k-btn" style="font-size:13px; padding:8px 16px; color:rgba(26,26,26,0.5);">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Site --}}
<div id="modal-edit" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:520px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-edit')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-5">Edit Site</h2>
        <form id="form-edit" method="POST" action="" class="space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Site name</label>
                <input type="text" id="edit-name" name="name" required class="k-input w-full">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Address (optional)</label>
                <input type="text" id="edit-address" name="address" class="k-input w-full">
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Latitude</label>
                    <input type="number" id="edit-lat" name="lat" step="0.000001" required class="k-input w-full font-mono">
                </div>
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Longitude</label>
                    <input type="number" id="edit-lng" name="lng" step="0.000001" required class="k-input w-full font-mono">
                </div>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:rgba(26,26,26,0.55);">Geofence radius (metres, optional)</label>
                <input type="number" id="edit-geofence" name="geofence_radius_m" min="10" max="50000" class="k-input w-full">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="k-btn" style="font-size:13px; padding:8px 20px;">Save Changes</button>
                <button type="button" onclick="closeModal('modal-edit')" class="k-btn" style="font-size:13px; padding:8px 16px; color:rgba(26,26,26,0.5);">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete Site --}}
<div id="modal-delete" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.35);">
    <div class="k-card" style="width:400px; max-width:90vw; padding:28px; position:relative;">
        <button onclick="closeModal('modal-delete')" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:rgba(26,26,26,0.4);">✕</button>
        <h2 class="font-display text-lg mb-2">Delete Site</h2>
        <p id="delete-label" class="text-sm mb-5" style="color:rgba(26,26,26,0.6);"></p>
        <form id="form-delete" method="POST" action="">
            @csrf
            @method('DELETE')
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

    function openEditModal(id, name, address, lat, lng, geofence) {
        document.getElementById('edit-name').value     = name;
        document.getElementById('edit-address').value  = address;
        document.getElementById('edit-lat').value      = lat;
        document.getElementById('edit-lng').value      = lng;
        document.getElementById('edit-geofence').value = geofence;
        document.getElementById('form-edit').action    = '/dashboard/sites/' + id;
        openModal('modal-edit');
    }

    function openDeleteModal(id, name) {
        document.getElementById('delete-label').textContent = '"' + name + '" will be permanently deleted.';
        document.getElementById('form-delete').action = '/dashboard/sites/' + id;
        openModal('modal-delete');
    }

    @if ($errors->any())
        openModal('modal-create');
    @endif
</script>
@endpush
