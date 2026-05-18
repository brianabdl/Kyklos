<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter PIN — Kyklos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Architects+Daughter&family=Roboto:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper text-ink min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="font-display text-5xl text-ink mb-1">Kyklos</div>
            <div class="text-sm" style="color: rgba(26,26,26,0.55);">Enter your 4-digit PIN</div>
        </div>

        <div class="k-card k-shadow p-8">

            @if ($errors->any())
                <div class="k-card-accent mb-6 px-4 py-3 text-sm" style="font-size:13px;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('employee.pin.submit') }}">
                @csrf

                <div class="mb-8">
                    <label class="block text-xs mb-1" style="color: rgba(26,26,26,0.55); font-family: var(--font-sans);">PIN</label>
                    <input
                        type="password"
                        name="pin"
                        inputmode="numeric"
                        pattern="[0-9]{4}"
                        maxlength="4"
                        autofocus
                        autocomplete="off"
                        class="k-input text-center text-2xl tracking-widest"
                        placeholder="••••"
                        style="font-family: var(--font-mono); letter-spacing: 0.4em;"
                    >
                </div>

                <button type="submit" class="k-btn k-btn-accent w-full" style="font-size:15px; padding: 12px 20px;">
                    Verify PIN →
                </button>
            </form>
        </div>

        <div class="text-center mt-4 text-xs" style="color: rgba(26,26,26,0.45);">
            <a href="{{ route('employee.login') }}" class="underline">← Back to sign in</a>
        </div>
    </div>

</body>
</html>
