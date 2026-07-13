<x-layouts.app title="Settings">
    <section class="w-full max-w-4xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Settings</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Interface settings</h1>
            <p class="mt-2 max-w-2xl text-sm text-[#706f6c] dark:text-[#A1A09A]">
                Steuere, ob das schwebende Terminal sichtbar ist und ob detaillierte Debug-Ausgaben dort gespeichert werden.
            </p>
        </div>

        <form method="POST" action="{{ route('settings.update') }}" class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            @csrf
            @method('PATCH')

            <div class="flex flex-col gap-5">
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
                    Save settings
                </button>
                <a href="{{ route('dashboard') }}" class="rounded-sm border border-[#19140035] px-5 py-2.5 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                    Back to dashboard
                </a>
            </div>
        </form>
    </section>
</x-layouts.app>
