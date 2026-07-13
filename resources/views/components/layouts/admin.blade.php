<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name', 'Laravel') }} | Admin</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] antialiased dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <div class="flex min-h-screen flex-col">
            <x-navbar />

            <div class="flex flex-1">
                {{-- Mobile sidebar toggle --}}
                <button
                    id="admin-sidebar-toggle"
                    type="button"
                    class="fixed bottom-4 right-4 z-50 flex size-12 items-center justify-center rounded-full bg-[#f53003] text-white shadow-lg hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e] md:hidden"
                    aria-label="Toggle sidebar"
                >
                    <svg id="admin-sidebar-icon-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75ZM2 10a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 10Zm0 5.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>

                    <svg id="admin-sidebar-icon-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="hidden size-6">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>

                {{-- Overlay --}}
                <div
                    id="admin-sidebar-overlay"
                    class="fixed inset-0 z-30 hidden bg-black/30 md:hidden"
                ></div>

                <x-admin.sidebar />

                <main class="flex-1 px-6 py-10">
                    @if (session('flash.success'))
                        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800/30 dark:bg-green-900/20 dark:text-green-300">
                            {{ session('flash.success') }}
                        </div>
                    @endif

                    @if (session('flash.error'))
                        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800/30 dark:bg-red-900/20 dark:text-red-300">
                            {{ session('flash.error') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var sidebar = document.getElementById('admin-sidebar');
                var overlay = document.getElementById('admin-sidebar-overlay');
                var toggleBtn = document.getElementById('admin-sidebar-toggle');
                var closeBtn = document.getElementById('admin-sidebar-close');
                var iconOpen = document.getElementById('admin-sidebar-icon-open');
                var iconClose = document.getElementById('admin-sidebar-icon-close');

                function openSidebar() {
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('translate-x-0');
                    overlay.classList.remove('hidden');
                    toggleBtn.classList.add('hidden');
                    iconOpen.classList.add('hidden');
                    iconClose.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                }

                function closeSidebar() {
                    sidebar.classList.remove('translate-x-0');
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                    toggleBtn.classList.remove('hidden');
                    iconOpen.classList.remove('hidden');
                    iconClose.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }

                if (toggleBtn) {
                    toggleBtn.addEventListener('click', openSidebar);
                }

                if (closeBtn) {
                    closeBtn.addEventListener('click', closeSidebar);
                }

                if (overlay) {
                    overlay.addEventListener('click', closeSidebar);
                }

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        closeSidebar();
                    }
                });
            });
        </script>
    </body>
</html>
