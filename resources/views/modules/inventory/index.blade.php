<x-layouts.app title="Inventory">
    <section class="w-full max-w-5xl">
        @if (session('status'))
            <div class="mb-6 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-800 shadow-[inset_0_0_0_1px_rgba(22,101,52,0.16)] dark:bg-green-950 dark:text-green-200 dark:shadow-[inset_0_0_0_1px_rgba(187,247,208,0.18)]">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 shadow-[inset_0_0_0_1px_rgba(220,38,38,0.16)] dark:bg-red-950 dark:text-red-200 dark:shadow-[inset_0_0_0_1px_rgba(252,165,165,0.18)]">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div>
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Inventory</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">My items</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                    All items you own, whether purchased or received as a gift.
                </p>
            </div>
        </div>

        @if ($items->isEmpty())
            <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">You don't own any items yet. <a href="{{ route('shop.index') }}" class="text-[#f53003] hover:underline dark:text-[#FF4433]">Visit the shop</a> to purchase some.</p>
            </div>
        @else
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $group)
                    @php $item = $group->shopItem; @endphp
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        @if ($item->image_path)
                            <img src="{{ Storage::url($item->image_path) }}" alt="{{ $item->name }}" class="mb-4 h-40 w-full rounded-xl object-cover" />
                        @else
                            <div class="mb-4 flex h-40 w-full items-center justify-center rounded-xl bg-[#19140005] dark:bg-[#fffaed05]">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-12 text-[#706f6c] dark:text-[#A1A09A]">
                                    <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 0 0-4.242 0l-7 7a3 3 0 0 0 4.241 4.243h.001l.497-.5a.75.75 0 0 1 1.064 1.057l-.498.501-.002.002a4.5 4.5 0 0 1-6.364-6.364l7-7a4.5 4.5 0 0 1 6.368 6.36l-3.455 3.553A2.625 2.625 0 1 1 9.52 9.52l3.45-3.451a.75.75 0 1 1 1.061 1.06l-3.45 3.451a1.125 1.125 0 0 0 1.587 1.595l3.454-3.553a3 3 0 0 0 0-4.242Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        @endif

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold">{{ $item->name }}</h3>
                                @if ($item->short_description)
                                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $item->short_description }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 rounded-full bg-[#f53003]/10 px-3 py-1 text-sm font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">
                                x{{ $group->total_quantity }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-center gap-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            @if ($group->source === 'gift')
                                <span class="rounded-full bg-purple-100 px-2 py-0.5 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                    Gift
                                </span>
                                @if ($group->giftedBy)
                                    <span>from {{ $group->giftedBy->name }}</span>
                                @endif
                            @else
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                    Purchased
                                </span>
                            @endif
                            <span>{{ $group->last_acquired->diffForHumans() }}</span>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-2">
                            <button
                                type="button"
                                onclick="document.getElementById('use-form-{{ $group->source }}-{{ $item->id }}').classList.toggle('hidden')"
                                class="rounded-lg bg-[#f53003] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]"
                            >
                                Use
                            </button>

                            <button
                                type="button"
                                onclick="document.getElementById('gift-form-{{ $group->source }}-{{ $item->id }}').classList.toggle('hidden')"
                                class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                            >
                                Gift
                            </button>
                        </div>

                        <form id="use-form-{{ $group->source }}-{{ $item->id }}" method="POST" action="{{ route('inventory.use') }}" class="mt-3 hidden">
                            @csrf
                            <input type="hidden" name="shop_item_id" value="{{ $item->id }}" />
                            <input type="hidden" name="source" value="{{ $group->source }}" />
                            @isset($group->purchase_id)
                                <input type="hidden" name="purchase_id" value="{{ $group->purchase_id }}" />
                            @endisset
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Use item?</span>
                                @if ($group->total_quantity > 1)
                                    <input
                                        type="number"
                                        name="quantity"
                                        value="1"
                                        min="1"
                                        max="{{ $group->total_quantity }}"
                                        class="w-16 rounded-lg border border-[#19140020] bg-white px-2 py-1.5 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                    />
                                @endif
                                <button type="submit" class="rounded-lg bg-[#f53003] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                                    Confirm
                                </button>
                            </div>
                        </form>

                        <form id="gift-form-{{ $group->source }}-{{ $item->id }}" method="POST" action="{{ route('inventory.gift') }}" class="mt-3 hidden">
                            @csrf
                            <input type="hidden" name="shop_item_id" value="{{ $item->id }}" />
                            <input type="hidden" name="source" value="{{ $group->source }}" />
                            @isset($group->purchase_id)
                                <input type="hidden" name="purchase_id" value="{{ $group->purchase_id }}" />
                            @endisset
                            <div class="flex items-center gap-2">
                                <select name="recipient_id" class="flex-1 rounded-lg border border-[#19140020] bg-white px-3 py-1.5 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]">
                                    <option value="">Select recipient...</option>
                                    @foreach (\App\Models\User::where('id', '!=', auth()->id())->orderBy('name')->get() as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @if ($group->total_quantity > 1)
                                    <input
                                        type="number"
                                        name="quantity"
                                        value="1"
                                        min="1"
                                        max="{{ $group->total_quantity }}"
                                        class="w-16 rounded-lg border border-[#19140020] bg-white px-2 py-1.5 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                    />
                                @endif
                                <button type="submit" class="rounded-lg bg-[#f53003] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                                    Send
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.app>
