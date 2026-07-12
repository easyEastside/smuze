<x-layouts.app title="Server">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Meine Server</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                        Deine Server mit manuell installiertem Agent.
                    </p>
                </div>
                <a
                    href="{{ route('server.create') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                    </svg>
                    Server hinzufügen
                </a>
            </div>
        </div>

        @if ($servers->isEmpty())
            <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Du hast noch keine Server. <a href="{{ route('server.create') }}" class="text-[#f53003] hover:underline dark:text-[#FF4433]">Ersten Server hinzufügen</a>
                </p>
            </div>
        @else
            <div class="mt-6 overflow-x-auto rounded-2xl bg-white shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[#19140020] text-left text-xs font-medium text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Name</th>
                            <th class="px-5 py-3 font-medium">Host</th>
                            <th class="px-5 py-3 font-medium">Agent-Port</th>
                            <th class="px-5 py-3 font-medium">Agent</th>
                            <th class="px-5 py-3 font-medium">Notizen</th>
                            <th class="px-5 py-3 font-medium text-right">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#19140020] dark:divide-[#3E3E3A]">
                        @foreach ($servers as $server)
                            <tr class="hover:bg-[#19140008] dark:hover:bg-[#fffaed08]">
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="size-2.5 rounded-full {{ $server->is_reachable ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                        <span class="text-xs {{ $server->is_reachable ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $server->is_reachable ? 'Online' : 'Offline' }}
                                        </span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                    <a href="{{ route('server.system', $server) }}" class="hover:text-[#f53003] dark:hover:text-[#FF4433]">
                                        {{ $server->name }}
                                    </a>
                                </td>
                                <td class="px-5 py-4 text-[#706f6c] dark:text-[#A1A09A] font-mono text-xs">{{ $server->host }}</td>
                                <td class="px-5 py-4 text-[#706f6c] dark:text-[#A1A09A]">{{ $server->agent_port ?? config('agent.push_port', 9300) }}</td>
                                <td class="px-5 py-4">
                                    @if ($server->agent_enabled && $server->agent_status === 'connected')
                                        <span class="rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-300">
                                            Verbunden
                                        </span>
                                    @else
                                        <span class="rounded-md bg-[#19140020] px-2 py-0.5 text-xs text-[#706f6c] dark:bg-[#3E3E3A] dark:text-[#A1A09A]">
                                            Ausstehend
                                        </span>
                                    @endif
                                </td>
                                <td class="max-w-[160px] truncate px-5 py-4 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    {{ $server->notes ?: '—' }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a
                                            href="{{ route('server.system', $server) }}"
                                            class="rounded-lg px-3 py-1.5 text-xs font-medium text-white bg-[#f53003] hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]"
                                        >
                                            System
                                        </a>
                                        <a
                                            href="{{ route('server.edit', $server) }}"
                                            class="rounded-lg border border-[#19140035] px-3 py-1.5 text-xs font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                                        >
                                            Bearbeiten
                                        </a>
                                        <form action="{{ route('server.destroy', $server) }}" method="POST" class="inline" onsubmit="return confirm('Server {{ $server->name }} löschen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-xs font-medium text-[#f53003] hover:border-[#f53003] dark:border-[#3E3E3A] dark:text-[#FF4433] dark:hover:border-[#FF4433]">
                                                Löschen
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>


        @endif
    </section>
</x-layouts.app>
