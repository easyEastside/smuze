<x-layouts.app title="Server bearbeiten">
    <section class="w-full max-w-2xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div>
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Server bearbeiten</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                    Bearbeite die Details für <strong>{{ $server->name }}</strong>.
                </p>
            </div>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <form method="POST" action="{{ route('server.update', $server) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Name</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name', $server->name) }}"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('name') border-[#f53003] @enderror"
                        />
                        @error('name')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="host" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Host</label>
                        <input
                            type="text"
                            name="host"
                            id="host"
                            value="{{ old('host', $server->host) }}"
                            placeholder="example.com oder 192.168.1.1"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('host') border-[#f53003] @enderror"
                        />
                        @error('host')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="agent_port" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Agent-Port</label>
                        <input
                            type="number"
                            name="agent_port"
                            id="agent_port"
                            value="{{ old('agent_port', $server->agent_port ?? config('agent.push_port', 9300)) }}"
                            min="1"
                            max="65535"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('agent_port') border-[#f53003] @enderror"
                        />
                        @error('agent_port')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Notizen (optional)</label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="2"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                        >{{ old('notes', $server->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                        Server speichern
                    </button>
                    <a href="{{ route('server.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Abbrechen</a>
                </div>
            </form>
        </div>
    </section>

</x-layouts.app>
