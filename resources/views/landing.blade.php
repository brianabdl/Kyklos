<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kyklos — Workforce Clock-In System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Architects+Daughter&family=Roboto:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper text-ink min-h-screen">

    {{-- Nav --}}
    <nav class="flex items-center justify-between px-6 py-4 max-w-5xl mx-auto">
        <div class="font-display text-2xl text-ink">Kyklos</div>
        <div class="flex gap-2">
            <a href="{{ route('employee.login') }}" class="k-btn" style="font-size:14px; padding: 8px 18px;">
                Employee Clock-In →
            </a>
            <a href="{{ route('dashboard.login') }}" class="k-btn k-btn-accent" style="font-size:14px; padding: 8px 18px;">
                Manager Portal →
            </a>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="max-w-5xl mx-auto px-6 pt-16 pb-20 text-center">
        <div class="inline-block k-pill k-pill-accent mb-6" style="font-family: var(--font-display);">
            Workforce Time Tracking
        </div>

        <h1 class="font-display text-6xl leading-tight mb-6" style="font-size: clamp(2.5rem, 6vw, 4.5rem);">
            Clock in. Clock out.<br>Stay in the loop.
        </h1>

        <p class="text-base mb-10 max-w-md mx-auto" style="color: rgba(26,26,26,0.6); line-height: 1.7;">
            Kyklos makes shift tracking effortless for your team — GPS-verified punches,
            automatic break records, and real-time oversight for managers.
        </p>

        <div class="flex items-center justify-center gap-4 flex-wrap">
            <a href="{{ route('employee.login') }}" class="k-btn k-btn-accent" style="font-size:15px; padding: 12px 28px;">
                Employee Clock-In →
            </a>
            <a href="{{ route('dashboard.login') }}" class="k-btn" style="font-size:15px; padding: 12px 28px;">
                Manager Portal →
            </a>
        </div>
    </section>

    {{-- Feature cards --}}
    <section id="features" class="max-w-5xl mx-auto px-6 pb-24">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

            <div class="k-card k-shadow p-6">
                <div class="text-2xl mb-3">⏱</div>
                <h3 class="font-display text-lg mb-2">Punch Sessions</h3>
                <p class="text-sm" style="color: rgba(26,26,26,0.6); line-height: 1.6;">
                    Clock in, start breaks, and clock out with a tap. Every state change
                    is logged to an immutable audit trail.
                </p>
            </div>

            <div class="k-card k-shadow p-6">
                <div class="text-2xl mb-3">📍</div>
                <h3 class="font-display text-lg mb-2">GPS Geofencing</h3>
                <p class="text-sm" style="color: rgba(26,26,26,0.6); line-height: 1.6;">
                    Punches are validated against site coordinates so employees can only
                    clock in when they're actually on-site.
                </p>
            </div>

            <div class="k-card k-shadow p-6">
                <div class="text-2xl mb-3">👥</div>
                <h3 class="font-display text-lg mb-2">Team Overview</h3>
                <p class="text-sm" style="color: rgba(26,26,26,0.6); line-height: 1.6;">
                    Managers see who's clocked in right now, who's on break, and who's
                    already wrapped up — all in one place.
                </p>
            </div>

            <div class="k-card k-shadow p-6">
                <div class="text-2xl mb-3">🔔</div>
                <h3 class="font-display text-lg mb-2">Push Notifications</h3>
                <p class="text-sm" style="color: rgba(26,26,26,0.6); line-height: 1.6;">
                    Firebase-powered alerts keep employees and managers informed about
                    shift changes, approvals, and reminders.
                </p>
            </div>

            <div class="k-card k-shadow p-6">
                <div class="text-2xl mb-3">📊</div>
                <h3 class="font-display text-lg mb-2">Shift Reports</h3>
                <p class="text-sm" style="color: rgba(26,26,26,0.6); line-height: 1.6;">
                    Generate saved reports on hours worked, break durations, and
                    attendance patterns across your whole organisation.
                </p>
            </div>

            <div class="k-card k-shadow p-6">
                <div class="text-2xl mb-3">🔐</div>
                <h3 class="font-display text-lg mb-2">2FA + OAuth</h3>
                <p class="text-sm" style="color: rgba(26,26,26,0.6); line-height: 1.6;">
                    PIN-based two-factor auth and Google / Apple sign-in keep
                    accounts secure without friction.
                </p>
            </div>

        </div>
    </section>

    {{-- CTA footer --}}
    <footer class="border-t border-ink/15 py-12 text-center">
        <div class="font-display text-3xl mb-3">Ready to track your team?</div>
        <p class="text-sm mb-6" style="color: rgba(26,26,26,0.55);">Pick your portal to get started.</p>
        <div class="flex items-center justify-center gap-4 flex-wrap">
            <a href="{{ route('employee.login') }}" class="k-btn k-btn-accent" style="font-size:15px; padding: 12px 28px;">
                Employee Clock-In →
            </a>
            <a href="{{ route('dashboard.login') }}" class="k-btn" style="font-size:15px; padding: 12px 28px;">
                Manager Portal →
            </a>
        </div>
        <div class="mt-10 text-xs" style="color: rgba(26,26,26,0.35); font-family: var(--font-sans);">
            &copy; {{ date('Y') }} Kyklos. All rights reserved.
        </div>
    </footer>

</body>
</html>
