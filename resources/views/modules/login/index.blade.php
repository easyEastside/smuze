<x-layouts.guest title="Log in">
    <div class="mb-8">
        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Welcome back</p>
        <h1 class="mt-2 text-2xl font-semibold">Log in to your account</h1>
        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Use your email address and password to continue.</p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-sm bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
        @csrf

        <div class="flex flex-col gap-2">
            <label for="email" class="text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
            @error('email')
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col gap-2">
            <label for="password" class="text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
            @error('password')
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between gap-4 text-sm">
            <label class="flex items-center gap-2 text-[#706f6c] dark:text-[#A1A09A]">
                <input name="remember" type="checkbox" value="1" class="rounded border-[#19140035] bg-transparent">
                Remember me
            </label>

            <a href="{{ route('password.request') }}" class="text-[#f53003] underline underline-offset-4 dark:text-[#FF4433]">Forgot password?</a>
        </div>

        <button type="submit" class="rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
            Log in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
        No account yet?
        <a href="{{ route('register') }}" class="font-medium text-[#f53003] underline underline-offset-4 dark:text-[#FF4433]">Register</a>
    </p>
</x-layouts.guest>
