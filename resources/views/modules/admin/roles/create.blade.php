<x-layouts.admin title="Create Role">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Create role</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Add a new role with optional permissions.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.roles.store') }}" class="mt-6">
        @csrf

        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="max-w-xl">
                <label for="name" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Role name</label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('name') border-[#f53003] @enderror"
                />
                @error('name')
                    <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-8">
                <p class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Permissions</p>
                <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Select which permissions this role should have.</p>

                @if ($permissions->isNotEmpty())
                    <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($permissions as $permission)
                            <label class="flex items-center gap-2 rounded-lg border border-[#19140020] px-3 py-2 text-sm dark:border-[#3E3E3A]">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->id }}"
                                    @checked(in_array($permission->id, old('permissions', [])))
                                    class="rounded border-[#19140020] text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                />
                                {{ $permission->name }}
                            </label>
                        @endforeach
                    </div>
                @else
                    <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">No permissions available yet. Create some first.</p>
                @endif
            </div>

            <div class="mt-8 flex items-center gap-3">
                <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                    Create role
                </button>
                <a href="{{ route('admin.roles.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Cancel</a>
            </div>
        </div>
    </form>
</x-layouts.admin>
