<x-layouts.admin title="Gift Item">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <h1 class="text-2xl font-semibold tracking-tight">Gift Item to User</h1>

        @if (session('flash.success'))
            <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-800 shadow-[inset_0_0_0_1px_rgba(22,101,52,0.16)] dark:bg-green-950 dark:text-green-200 dark:shadow-[inset_0_0_0_1px_rgba(187,247,208,0.18)]">
                {{ session('flash.success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.inventory.store') }}" class="mt-6 space-y-6">
            @csrf

            <div>
                <label for="user_id" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">User</label>
                <select name="user_id" id="user_id" class="mt-2 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]">
                    <option value="">Select user...</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="shop_item_id" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Item</label>
                <select name="shop_item_id" id="shop_item_id" class="mt-2 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]">
                    <option value="">Select item...</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}" @selected(old('shop_item_id') == $item->id)>{{ $item->name }} ({{ $item->price }} credits)</option>
                    @endforeach
                </select>
                @error('shop_item_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="quantity" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Quantity</label>
                <input type="number" name="quantity" id="quantity" value="{{ old('quantity', 1) }}" min="1" class="mt-2 w-20 rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]" />
                @error('quantity')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" class="rounded-lg bg-[#f53003] px-6 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                    Gift Item
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
