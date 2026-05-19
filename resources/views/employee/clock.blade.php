@extends('employee.layout')

@section('title', 'Clock In')

@section('content')

@php
    $now = now();
@endphp

{{-- Greeting + date --}}
<div class="mb-6">
    <div class="font-display text-3xl mb-1">
        {{ $now->hour < 12 ? 'Good morning' : ($now->hour < 17 ? 'Good afternoon' : 'Good evening') }},
        {{ explode(' ', auth()->user()->full_name)[0] }}.
    </div>
    <div class="text-sm" style="color: rgba(26,26,26,0.55);">{{ $now->format('l, d M Y') }}</div>
</div>

@if (!$activeSession)
{{-- ─────────────────── CLOCKED OUT ─────────────────── --}}

<div class="k-card k-shadow p-6">
    <div class="text-xs mb-5" style="color: rgba(26,26,26,0.45); text-transform: uppercase; letter-spacing: 0.06em;">Not clocked in</div>

    <form method="POST" action="{{ route('employee.clock.in') }}" id="clock-form">
        @csrf

        {{-- Hidden fields for GPS --}}
        <input type="hidden" name="lat" id="lat">
        <input type="hidden" name="lng" id="lng">
        <input type="hidden" name="method" id="method" value="manual">

        {{-- Site selector --}}
        <div class="mb-5">
            <label class="block text-xs mb-1" style="color: rgba(26,26,26,0.55);">Site</label>
            <select name="site_id" class="k-input" required>
                <option value="" disabled selected>Select a site…</option>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Method picker --}}
        <div class="mb-5">
            <label class="block text-xs mb-2" style="color: rgba(26,26,26,0.55);">Clock-in method</label>
            <div class="flex gap-2">
                <label class="method-pill active" data-method="manual">
                    <input type="radio" name="_method_pick" value="manual" checked class="sr-only"> Manual
                </label>
                <label class="method-pill" data-method="gps">
                    <input type="radio" name="_method_pick" value="gps" class="sr-only"> GPS
                </label>
                <label class="method-pill" data-method="qr">
                    <input type="radio" name="_method_pick" value="qr" class="sr-only"> QR Code
                </label>
            </div>
        </div>

        {{-- GPS status panel --}}
        <div id="panel-gps" class="method-panel hidden mb-4">
            <div class="k-card px-4 py-3 text-sm" id="gps-status" style="color: rgba(26,26,26,0.6);">
                Press "Get Location" to capture your position.
            </div>
            <button type="button" id="gps-btn" class="k-btn mt-2 text-sm" style="padding: 6px 14px;">
                Get Location
            </button>
        </div>

        {{-- QR scanner panel --}}
        <div id="panel-qr" class="method-panel hidden mb-4">
            <div class="k-card overflow-hidden" style="border-radius: 8px;">
                <video id="qr-video" autoplay muted playsinline style="width:100%; display:block; max-height:240px; object-fit:cover;"></video>
                <canvas id="qr-canvas" style="display:none;"></canvas>
            </div>
            <div class="text-xs mt-2" style="color: rgba(26,26,26,0.45);">Point camera at a Kyklos site QR code.</div>
            <div id="qr-status" class="text-xs mt-1" style="color: rgba(26,26,26,0.55);"></div>
        </div>

        <button type="submit" id="submit-btn" class="k-btn k-btn-accent w-full mt-2" style="font-size:15px; padding:12px 20px;">
            Clock In →
        </button>
    </form>
</div>

@elseif ($activeSession->state === 'active')
{{-- ─────────────────── CLOCKED IN ─────────────────── --}}

<div class="k-card k-shadow p-6">
    <div class="text-xs mb-1" style="color: rgba(26,26,26,0.45); text-transform: uppercase; letter-spacing: 0.06em;">On the clock</div>
    <div class="font-display text-2xl mb-4">{{ $activeSession->site->name }}</div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
            <div class="text-xs mb-0.5" style="color: rgba(26,26,26,0.45);">Clocked in at</div>
            <div class="font-mono text-lg">{{ $activeSession->clocked_in_at->format('H:i') }}</div>
        </div>
        <div>
            <div class="text-xs mb-0.5" style="color: rgba(26,26,26,0.45);">Elapsed</div>
            <div class="font-mono text-lg" id="elapsed-timer">—</div>
        </div>
    </div>

    <div class="flex gap-3">
        <form method="POST" action="{{ route('employee.break.start') }}" class="flex-1">
            @csrf
            <button type="submit" class="k-btn w-full" style="padding:10px 16px;">Start Break</button>
        </form>

        <form method="POST" action="{{ route('employee.clock.out') }}" class="flex-1">
            @csrf
            <input type="hidden" name="method" value="manual">
            <button type="submit" class="k-btn k-btn-accent w-full" style="padding:10px 16px;">Clock Out →</button>
        </form>
    </div>
</div>

@elseif ($activeSession->state === 'on_break')
{{-- ─────────────────── ON BREAK ─────────────────── --}}

@php
    $openBreak = $activeSession->breaks->where('ended_at', null)->first();
@endphp

<div class="k-card k-shadow p-6">
    <div class="text-xs mb-1" style="color: rgba(26,26,26,0.45); text-transform: uppercase; letter-spacing: 0.06em;">On break</div>
    <div class="font-display text-2xl mb-4">{{ $activeSession->site->name }}</div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
            <div class="text-xs mb-0.5" style="color: rgba(26,26,26,0.45);">Break started</div>
            <div class="font-mono text-lg">{{ $openBreak?->started_at->format('H:i') ?? '—' }}</div>
        </div>
        <div>
            <div class="text-xs mb-0.5" style="color: rgba(26,26,26,0.45);">Break duration</div>
            <div class="font-mono text-lg" id="break-timer">—</div>
        </div>
    </div>

    <div class="flex gap-3">
        <form method="POST" action="{{ route('employee.break.end') }}" class="flex-1">
            @csrf
            <button type="submit" class="k-btn w-full" style="padding:10px 16px;">End Break</button>
        </form>

        <form method="POST" action="{{ route('employee.clock.out') }}" class="flex-1">
            @csrf
            <input type="hidden" name="method" value="manual">
            <button type="submit" class="k-btn k-btn-accent w-full" style="padding:10px 16px;">Clock Out →</button>
        </form>
    </div>
</div>

@endif

@endsection

@push('scripts')
<script>
// ── Live timer ──────────────────────────────────────────────
function formatElapsed(startIso) {
    const diff = Math.floor((Date.now() - new Date(startIso)) / 1000);
    const h = Math.floor(diff / 3600);
    const m = Math.floor((diff % 3600) / 60);
    const s = diff % 60;
    return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

@if ($activeSession && $activeSession->state === 'active')
(function () {
    const el = document.getElementById('elapsed-timer');
    const start = '{{ $activeSession->clocked_in_at->toIso8601String() }}';
    function tick() { el.textContent = formatElapsed(start); }
    tick();
    setInterval(tick, 1000);
})();
@endif

@if ($activeSession && $activeSession->state === 'on_break')
@php $openBreak = $activeSession->breaks->where('ended_at', null)->first(); @endphp
@if ($openBreak)
(function () {
    const el = document.getElementById('break-timer');
    const start = '{{ $openBreak->started_at->toIso8601String() }}';
    function tick() { el.textContent = formatElapsed(start); }
    tick();
    setInterval(tick, 1000);
})();
@endif
@endif

// ── Method picker ────────────────────────────────────────────
@if (!$activeSession)
(function () {
    const pills   = document.querySelectorAll('.method-pill');
    const methodEl = document.getElementById('method');
    let qrStream  = null;

    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            pills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            const m = pill.dataset.method;
            methodEl.value = m;

            document.querySelectorAll('.method-panel').forEach(p => p.classList.add('hidden'));

            if (m === 'gps') {
                document.getElementById('panel-gps').classList.remove('hidden');
                stopQr();
            } else if (m === 'qr') {
                document.getElementById('panel-qr').classList.remove('hidden');
                startQr();
            } else {
                stopQr();
            }
        });
    });

    // GPS
    document.getElementById('gps-btn').addEventListener('click', () => {
        const status = document.getElementById('gps-status');
        status.textContent = 'Getting location…';
        navigator.geolocation.getCurrentPosition(
            pos => {
                document.getElementById('lat').value = pos.coords.latitude;
                document.getElementById('lng').value = pos.coords.longitude;
                status.textContent = `Location captured (±${Math.round(pos.coords.accuracy)} m)`;
                status.style.color = '#16a34a';
                console.log('GPS position:', pos.coords.latitude, pos.coords.longitude, `(accuracy: ${pos.coords.accuracy} m)`);
            },
            err => {
                status.textContent = `Location failed: ${err.message}`;
                status.style.color = 'var(--color-accent)';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });

    // QR
    function startQr() {
        const video  = document.getElementById('qr-video');
        const canvas = document.getElementById('qr-canvas');
        const ctx    = canvas.getContext('2d');
        const status = document.getElementById('qr-status');

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(stream => {
                qrStream = stream;
                video.srcObject = stream;
                video.play();
                scanFrame();
            })
            .catch(err => { status.textContent = `Camera error: ${err.message}`; });

        function scanFrame() {
            if (!qrStream) return;
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width  = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0);
                const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                // jsQR is loaded below — skip if not available
                if (typeof jsQR !== 'undefined') {
                    const code = jsQR(imgData.data, imgData.width, imgData.height);
                    if (code && code.data.startsWith('kyklos:site:')) {
                        const siteId = code.data.split(':')[2];
                        const sel = document.querySelector('[name="site_id"]');
                        if (sel) sel.value = siteId;
                        stopQr();
                        document.getElementById('clock-form').submit();
                        return;
                    }
                }
            }
            requestAnimationFrame(scanFrame);
        }
    }

    function stopQr() {
        if (qrStream) {
            qrStream.getTracks().forEach(t => t.stop());
            qrStream = null;
        }
    }
})();
@endif
</script>

{{-- jsQR for QR scanning --}}
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<style>
.method-pill {
    cursor: pointer;
    padding: 6px 14px;
    font-size: 13px;
    border: 1px solid var(--color-ink);
    border-radius: 4px;
    user-select: none;
    color: rgba(26,26,26,0.6);
    background: transparent;
    transition: background 0.1s, color 0.1s;
}
.method-pill.active {
    background: var(--color-ink);
    color: var(--color-paper);
}
</style>
@endpush
