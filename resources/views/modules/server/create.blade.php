<x-layouts.app title="Server hinzufügen">
    <section class="w-full max-w-2xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div>
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Server hinzufügen</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                    Registriere einen Server, auf dem du den Agent manuell installierst.
                </p>
            </div>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <form method="POST" action="{{ route('server.store') }}">
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
                        <label for="host" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Agent-Host</label>
                        <input
                            type="text"
                            name="host"
                            id="host"
                            value="{{ old('host') }}"
                            placeholder="agent.example.com oder 192.168.1.1"
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
                            value="{{ old('agent_port', config('agent.push_port', 9300)) }}"
                            min="1"
                            max="65535"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('agent_port') border-[#f53003] @enderror"
                        />
                        @error('agent_port')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="agent_public_url" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Agent Public URL (optional)</label>
                        <input
                            type="url"
                            name="agent_public_url"
                            id="agent_public_url"
                            value="{{ old('agent_public_url') }}"
                            placeholder="https://agent.example.com"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('agent_public_url') border-[#f53003] @enderror"
                        />
                        <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">F&uuml;r HTTPS/WSS-Terminals: z.B. die TLS-Reverse-Proxy-URL des Agent.</p>
                        @error('agent_public_url')
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
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                        Server erstellen
                    </button>
                    <a href="{{ route('server.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Abbrechen</a>
                </div>
            </form>
        </div>
    </section>

</x-layouts.app>
