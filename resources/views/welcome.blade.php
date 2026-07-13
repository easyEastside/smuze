<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>smuze – server management made smuze</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] antialiased dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <div class="flex min-h-screen flex-col">
            <header class="border-b border-[#19140020] bg-white/80 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#161615]/80">
                <nav class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                    <a href="{{ url('/') }}" class="text-sm font-semibold tracking-tight">smuze</a>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('login') }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Anmelden</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Registrieren</a>
                    </div>
                </nav>
            </header>

            <main class="flex flex-1 flex-col">
                <section class="flex flex-1 items-center justify-center px-6 py-16 sm:py-24">
                    <div class="w-full max-w-6xl">
                        <div class="mx-auto max-w-2xl text-center">
                            <p class="text-sm font-semibold text-[#f53003] dark:text-[#FF4433]">server management made smuze</p>
                            <h1 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl">
                                Deine Server.
                                <br>
                                <span class="text-[#f53003] dark:text-[#FF4433]">Ein Dashboard.</span>
                            </h1>
                            <p class="mt-4 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                                Überwache, verwalte und steuere deine Server von überall.
                                <br>
                                Metriken in Echtzeit, Logs, Firewall, Datenbanken und mehr – alles an einem Ort.
                            </p>

                            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                                    Jetzt loslegen
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg border border-[#19140035] px-5 py-2.5 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                    Konto erstellen
                                </a>
                            </div>
                        </div>

                        <div class="mt-20 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-xl border border-[#19140020] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-[#22c55e]/10">
                                    <svg class="size-5 text-[#22c55e]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2Zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2Z"/></svg>
                                </div>
                                <p class="mt-4 text-sm font-semibold">Monitoring & Metriken</p>
                                <p class="mt-1 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">CPU, RAM und Disk in Echtzeit. Historische Verlaufsgraphen mit frei wählbarem Zeitraum.</p>
                            </div>
                            <div class="rounded-xl border border-[#19140020] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-[#f59e0b]/10">
                                    <svg class="size-5 text-[#f59e0b]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                                </div>
                                <p class="mt-4 text-sm font-semibold">Firewall & Sicherheit</p>
                                <p class="mt-1 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">UFW-Regeln verwalten, Ports freigeben oder sperren. Einfach und schnell.</p>
                            </div>
                            <div class="rounded-xl border border-[#19140020] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-[#3b82f6]/10">
                                    <svg class="size-5 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                </div>
                                <p class="mt-4 text-sm font-semibold">Logs in Echtzeit</p>
                                <p class="mt-1 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">Systemlogs, Apache, Nginx, MySQL – mit Live-Follow, Filter und benutzerdefinierten Pfaden.</p>
                            </div>
                            <div class="rounded-xl border border-[#19140020] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-[#a855f7]/10">
                                    <svg class="size-5 text-[#a855f7]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                                </div>
                                <p class="mt-4 text-sm font-semibold">Datenbanken</p>
                                <p class="mt-1 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">MySQL-Datenbanken, Tabellen und User verwalten. Inklusive Rechteverwaltung.</p>
                            </div>
                            <div class="rounded-xl border border-[#19140020] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-[#f53003]/10">
                                    <svg class="size-5 text-[#f53003]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                                </div>
                                <p class="mt-4 text-sm font-semibold">Webserver</p>
                                <p class="mt-1 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">Apache und Nginx konfigurieren, VHosts anlegen, Module verwalten, SSL via Certbot.</p>
                            </div>
                            <div class="rounded-xl border border-[#19140020] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-[#06b6d4]/10">
                                    <svg class="size-5 text-[#06b6d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                </div>
                                <p class="mt-4 text-sm font-semibold">Terminal & Cron</p>
                                <p class="mt-1 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">Webbasiertes Terminal, Cronjob-Verwaltung und Dateimanager – alles im Browser.</p>
                            </div>
                        </div>

                        <div class="mt-16 text-center">
                            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                server management made smuze &middot; Laravel &middot; Go Agent &middot; Tailwind CSS
                            </p>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
