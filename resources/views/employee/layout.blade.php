<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Clock In') — Kyklos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Architects+Daughter&family=Roboto:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper text-ink min-h-screen flex flex-col">

    {{-- Top bar --}}
    <header class="border-b border-ink flex-shrink-0">
        <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <span class="font-display text-2xl">Kyklos</span>
                <nav class="flex gap-1">
                    <a href="{{ route('employee.clock') }}"
                       class="k-nav-link {{ request()->routeIs('employee.clock') ? 'active' : '' }}" style="font-size:13px; padding: 5px 10px;">
                        Clock
                    </a>
                    <a href="{{ route('employee.history') }}"
                       class="k-nav-link {{ request()->routeIs('employee.history') ? 'active' : '' }}" style="font-size:13px; padding: 5px 10px;">
                        History
                    </a>
                    <a href="{{ route('employee.shifts') }}"
                       class="k-nav-link {{ request()->routeIs('employee.shifts') ? 'active' : '' }}" style="font-size:13px; padding: 5px 10px;">
                        Shifts
                    </a>
                </nav>
            </div>

            <div class="flex items-center gap-4">
                <span class="text-xs" style="color: rgba(26,26,26,0.55);">{{ auth()->user()->full_name }}</span>
                <form method="POST" action="{{ route('employee.logout') }}">
                    @csrf
                    <button type="submit" class="k-btn text-xs" style="padding: 5px 10px;">Sign out</button>
                </form>
            </div>
        </div>
    </header>

    {{-- Page content --}}
    <main class="flex-1 max-w-2xl mx-auto w-full px-4 py-8">

        @if (session('success'))
            <div class="k-card mb-6 px-4 py-3 text-sm border-green-400" style="border-color: #4ade80; background: #f0fdf4;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('punch'))
            <div class="k-card-accent mb-6 px-4 py-3 text-sm">
                {{ $errors->first('punch') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
