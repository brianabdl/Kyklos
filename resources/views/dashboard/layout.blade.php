<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — Kyklos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Architects+Daughter&family=Roboto:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper text-ink flex h-screen overflow-hidden">

    {{-- Sidebar --}}
    <aside class="w-56 shrink-0 flex flex-col border-r border-ink bg-paper">
        {{-- Logo --}}
        <div class="px-6 py-6 border-b border-ink">
            <div class="font-display text-2xl text-ink leading-none">Kyklos</div>
            <div class="text-xs mt-1" style="color: rgba(26,26,26,0.55); font-family: var(--font-sans);">
                {{ auth()->user()->organization->name }}
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 py-4">
            <a href="{{ route('dashboard.overview') }}"
               class="k-nav-link {{ request()->routeIs('dashboard.overview') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Overview
            </a>
            <a href="{{ route('dashboard.team') }}"
               class="k-nav-link {{ request()->routeIs('dashboard.team') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Team
            </a>
            <a href="{{ route('dashboard.shifts') }}"
               class="k-nav-link {{ request()->routeIs('dashboard.shifts') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Shifts
            </a>
            <a href="{{ route('dashboard.logs') }}"
               class="k-nav-link {{ request()->routeIs('dashboard.logs') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Logs
            </a>
            <a href="{{ route('dashboard.sites') }}"
               class="k-nav-link {{ request()->routeIs('dashboard.sites') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Sites
            </a>
        </nav>

        {{-- Manager + Logout --}}
        <div class="px-4 py-4 border-t border-ink">
            <div class="text-xs mb-3" style="color: rgba(26,26,26,0.55);">
                <span class="font-semibold text-ink">{{ auth()->user()->full_name }}</span><br>
                Manager
            </div>
            <form method="POST" action="{{ route('dashboard.logout') }}">
                @csrf
                <button type="submit" class="k-btn text-sm w-full" style="font-size:13px; padding: 7px 12px;">
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    {{-- Main content --}}
    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Top bar --}}
        <header class="px-8 py-4 border-b border-ink flex items-center justify-between flex-shrink-0">
            <h1 class="font-display text-xl">@yield('heading', 'Overview')</h1>
            <span class="font-mono text-sm" style="color: rgba(26,26,26,0.55); font-size: 12px;">
                {{ now()->format('l, d M Y') }}
            </span>
        </header>

        {{-- Scrollable content --}}
        <div class="flex-1 overflow-y-auto px-8 py-6">
            @if (session('success'))
                <div class="mb-5 px-4 py-3 rounded text-sm font-semibold"
                     style="background: oklch(from var(--color-accent) l c h / 12%); border: 1px solid oklch(from var(--color-accent) l c h / 30%); color: var(--color-ink);">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-5 px-4 py-3 rounded text-sm"
                     style="background: oklch(55% 0.18 25 / 10%); border: 1px solid oklch(55% 0.18 25 / 30%); color: var(--color-ink);">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>
</html>
