<x-layouts.guest title="Forgot password">
    <div class="mb-8">
        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Password reset</p>
        <h1 class="mt-2 text-2xl font-semibold">Request a reset link</h1>
        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Enter your email address and we will send you a password reset link.</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
        @csrf

        <div class="flex flex-col gap-2">
            <label for="email" class="text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
            @error('email')
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
            Email password reset link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
        Remember your password?
        <a href="{{ route('login') }}" class="font-medium text-[#f53003] underline underline-offset-4 dark:text-[#FF4433]">Log in</a>
    </p>
</x-layouts.guest>
