<x-layouts.admin title="Edit Permission">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Edit permission</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Update permission <strong>{{ $permission->name }}</strong>.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.permissions.update', $permission) }}" class="mt-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="max-w-xl">
                <label for="name" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Permission name</label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name', $permission->name) }}"
                    class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('name') border-[#f53003] @enderror"
                />
                @error('name')
                    <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-8 flex items-center gap-3">
                <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                    Update permission
                </button>
                <a href="{{ route('admin.permissions.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Cancel</a>
            </div>
        </div>
    </form>
</x-layouts.admin>
