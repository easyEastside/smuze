<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] antialiased dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="flex min-h-screen flex-col items-center justify-center px-6 py-10">
            <a href="{{ url('/') }}" class="mb-8 text-sm font-semibold tracking-tight">
                {{ config('app.name', 'Laravel') }}
            </a>

            @if ($fullWidth ?? false)
                {{ $slot }}
            @else
                <section class="w-full max-w-md rounded-xl bg-white p-8 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                    {{ $slot }}
                </section>
            @endif
        </main>
    </body>
</html>
