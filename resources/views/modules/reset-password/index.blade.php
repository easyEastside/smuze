<x-layouts.guest title="Reset password">
    <div class="mb-8">
        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Choose a new password</p>
        <h1 class="mt-2 text-2xl font-semibold">Reset your password</h1>
        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Enter your email address and a new password.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="flex flex-col gap-2">
            <label for="email" class="text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', request('email')) }}" required autofocus autocomplete="username" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
            @error('email')
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col gap-2">
            <label for="password" class="text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
            @error('password')
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col gap-2">
            <label for="password_confirmation" class="text-sm font-medium">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
        </div>

        <button type="submit" class="rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
            Reset password
        </button>
    </form>
</x-layouts.guest>
