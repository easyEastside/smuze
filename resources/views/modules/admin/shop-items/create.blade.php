<x-layouts.admin title="Create Shop Item">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Create shop item</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Add a new item to the shop.
            </p>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <form method="POST" action="{{ route('admin.shop-items.store') }}" enctype="multipart/form-data" class="max-w-2xl">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Name</label>
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

                <div class="sm:col-span-2">
                    <label for="short_description" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Short description</label>
                    <input
                        type="text"
                        name="short_description"
                        id="short_description"
                        value="{{ old('short_description') }}"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('short_description') border-[#f53003] @enderror"
                    />
                    <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Shown in the shop overview.</p>
                    @error('short_description')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Full description</label>
                    <textarea
                        name="description"
                        id="description"
                        rows="4"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('description') border-[#f53003] @enderror"
                    >{{ old('description') }}</textarea>
                    <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Shown on the item detail page.</p>
                    @error('description')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="price" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Price (credits)</label>
                    <input
                        type="number"
                        name="price"
                        id="price"
                        value="{{ old('price') }}"
                        min="1"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('price') border-[#f53003] @enderror"
                    />
                    @error('price')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="stock" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Stock</label>
                    <input
                        type="number"
                        name="stock"
                        id="stock"
                        value="{{ old('stock') }}"
                        min="0"
                        placeholder="Leave empty for unlimited"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('stock') border-[#f53003] @enderror"
                    />
                    <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Leave empty for unlimited stock.</p>
                    @error('stock')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="image" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Image (optional)</label>
                    <input
                        type="file"
                        name="image"
                        id="image"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        class="mt-1 w-full text-sm text-[#706f6c] file:mr-3 file:rounded-lg file:border-0 file:bg-[#f53003]/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-[#f53003] hover:file:bg-[#f53003]/20 dark:text-[#A1A09A] dark:file:bg-[#FF4433]/15 dark:file:text-[#FF4433] dark:hover:file:bg-[#FF4433]/25"
                    />
                    @error('image')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', true))
                            class="rounded border-[#19140020] text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615]"
                        />
                        <span class="text-[#1b1b18] dark:text-[#EDEDEC]">Active (visible in shop)</span>
                    </label>
                </div>
            </div>

            <div class="mt-8 flex items-center gap-3">
                <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                    Create item
                </button>
                <a href="{{ route('admin.shop-items.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
