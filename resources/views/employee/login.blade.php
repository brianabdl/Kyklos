<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Sign In — Kyklos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Architects+Daughter&family=Roboto:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper text-ink min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="font-display text-5xl text-ink mb-1">Kyklos</div>
            <div class="text-sm" style="color: rgba(26,26,26,0.55);">Employee Clock-In</div>
        </div>

        <div class="k-card k-shadow p-8">

            @if ($errors->any())
                <div class="k-card-accent mb-6 px-4 py-3 text-sm" style="font-size:13px;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('employee.login.submit') }}">
                @csrf

                <div class="mb-6">
                    <label class="block text-xs mb-1" style="color: rgba(26,26,26,0.55); font-family: var(--font-sans);">Email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        autofocus
                        class="k-input"
                        placeholder="you@company.co"
                    >
                </div>

                <div class="mb-8">
                    <label class="block text-xs mb-1" style="color: rgba(26,26,26,0.55); font-family: var(--font-sans);">Password</label>
                    <input
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        class="k-input"
                        placeholder="••••••••"
                        style="font-family: var(--font-mono); letter-spacing: 0.15em;"
                    >
                </div>

                <button type="submit" class="k-btn k-btn-accent w-full" style="font-size:15px; padding: 12px 20px;">
                    Sign in →
                </button>
            </form>
        </div>

        <div class="text-center mt-4 text-xs" style="color: rgba(26,26,26,0.45);">
            Manager? <a href="{{ route('dashboard.login') }}" class="underline">Manager dashboard →</a>
        </div>
    </div>

</body>
</html>
