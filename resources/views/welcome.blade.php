<x-layouts.guest>
    <div class="text-center">
        <h1 class="text-2xl font-semibold">Welcome</h1>
        <p class="mt-3 text-sm text-[#706f6c] dark:text-[#A1A09A]">Choose how you want to continue.</p>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
                    Log in
                </a>

                <a href="{{ route('register') }}" class="rounded-sm border border-[#19140035] px-5 py-2.5 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                    Register
                </a>
            @endauth
        </div>
    </div>
</x-layouts.guest>
