@php
    $server = $server ?? null;
@endphp

<div class="sm:col-span-2 rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
    <div>
        <h2 class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">SSH-Optimierung</h2>
        <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Diese Werte steuern Verbindungsaufbau, Keepalive und Wiederverwendung der SSH-Verbindung für diesen Server.</p>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="ssh_connect_timeout" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Connect Timeout (Sek.)</label>
            <input
                type="number"
                name="ssh_connect_timeout"
                id="ssh_connect_timeout"
                value="{{ old('ssh_connect_timeout', $server?->ssh_connect_timeout ?? 5) }}"
                min="1"
                max="300"
                class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('ssh_connect_timeout') border-[#f53003] @enderror"
            />
            @error('ssh_connect_timeout')
                <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="ssh_command_timeout" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Command Timeout (Sek.)</label>
            <input
                type="number"
                name="ssh_command_timeout"
                id="ssh_command_timeout"
                value="{{ old('ssh_command_timeout', $server?->ssh_command_timeout ?? 30) }}"
                min="1"
                max="3600"
                class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('ssh_command_timeout') border-[#f53003] @enderror"
            />
            @error('ssh_command_timeout')
                <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="ssh_control_persist" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Control Persist (Min.)</label>
            <input
                type="number"
                name="ssh_control_persist"
                id="ssh_control_persist"
                value="{{ old('ssh_control_persist', $server?->ssh_control_persist ?? 30) }}"
                min="1"
                max="1440"
                class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('ssh_control_persist') border-[#f53003] @enderror"
            />
            @error('ssh_control_persist')
                <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="ssh_connection_attempts" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Verbindungsversuche</label>
            <input
                type="number"
                name="ssh_connection_attempts"
                id="ssh_connection_attempts"
                value="{{ old('ssh_connection_attempts', $server?->ssh_connection_attempts ?? 2) }}"
                min="1"
                max="5"
                class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('ssh_connection_attempts') border-[#f53003] @enderror"
            />
            @error('ssh_connection_attempts')
                <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="ssh_server_alive_interval" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Keepalive Interval (Sek.)</label>
            <input
                type="number"
                name="ssh_server_alive_interval"
                id="ssh_server_alive_interval"
                value="{{ old('ssh_server_alive_interval', $server?->ssh_server_alive_interval ?? 15) }}"
                min="1"
                max="300"
                class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('ssh_server_alive_interval') border-[#f53003] @enderror"
            />
            @error('ssh_server_alive_interval')
                <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="ssh_server_alive_count_max" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Keepalive Fehlerlimit</label>
            <input
                type="number"
                name="ssh_server_alive_count_max"
                id="ssh_server_alive_count_max"
                value="{{ old('ssh_server_alive_count_max', $server?->ssh_server_alive_count_max ?? 3) }}"
                min="1"
                max="10"
                class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('ssh_server_alive_count_max') border-[#f53003] @enderror"
            />
            @error('ssh_server_alive_count_max')
                <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label class="flex items-center gap-3">
                <input
                    type="checkbox"
                    name="ssh_compression"
                    id="ssh_compression"
                    value="1"
                    {{ old('ssh_compression', $server?->ssh_compression ?? false) ? 'checked' : '' }}
                    class="size-4 rounded border-[#19140020] dark:border-[#3E3E3A]"
                />
                <span class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">SSH-Kompression aktivieren</span>
            </label>
            <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Hilft bei langsamen Verbindungen, kann bei schnellen Netzen aber unnötige CPU-Last erzeugen.</p>
            @error('ssh_compression')
                <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
