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
                        <label for="port" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Port</label>
                        <input
                            type="number"
                            name="port"
                            id="port"
                            value="{{ old('port', $server->port) }}"
                            min="1"
                            max="65535"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('port') border-[#f53003] @enderror"
                        />
                        @error('port')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Benutzername</label>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            value="{{ old('username', $server->username) }}"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('username') border-[#f53003] @enderror"
                        />
                        @error('username')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="auth_type" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Authentifizierung</label>
                        <select
                            name="auth_type"
                            id="auth_type"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('auth_type') border-[#f53003] @enderror"
                        >
                            <option value="key" @selected(old('auth_type', $server->auth_type) === 'key')>SSH-Key</option>
                            <option value="password" @selected(old('auth_type', $server->auth_type) === 'password')>Passwort</option>
                        </select>
                        @error('auth_type')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="credentials-password" class="sm:col-span-2">
                        <label for="credentials" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Passwort</label>
                        <input
                            type="password"
                            name="credentials"
                            id="credentials"
                            value="{{ old('credentials', $server->auth_type === 'password' ? '' : '') }}"
                            placeholder="Neues Passwort (leer lassen, um nicht zu ändern)"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('credentials') border-[#f53003] @enderror"
                        />
                        @error('credentials')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="credentials-key" class="sm:col-span-2">
                        <label for="key_content" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">SSH-Private-Key</label>
                        <textarea
                            name="key_content"
                            id="key_content"
                            rows="6"
                            placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;..."
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm font-mono text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('key_content') border-[#f53003] @enderror"
                        >{{ old('key_content', $server->auth_type === 'key' && $server->key_content ? '' : '') }}</textarea>
                        @error('key_content')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Füge den Inhalt deines privaten SSH-Keys ein oder lasse leer, um den bestehenden Key zu behalten.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="key_path" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">SSH-Key Pfad (optional)</label>
                        <input
                            type="text"
                            name="key_path"
                            id="key_path"
                            value="{{ old('key_path', $server->key_path) }}"
                            placeholder="/home/user/.ssh/id_ed25519"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('key_path') border-[#f53003] @enderror"
                        />
                        @error('key_path')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                name="use_sudo"
                                id="use_sudo"
                                value="1"
                                {{ old('use_sudo', $server->use_sudo) ? 'checked' : '' }}
                                class="size-4 rounded border-[#19140020] dark:border-[#3E3E3A]"
                            />
                            <span class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">sudo verwenden</span>
                        </label>
                    </div>

                    @include('modules.server.partials.ssh-options', ['server' => $server])

                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Notizen (optional)</label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="2"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('notes') border-[#f53003] @enderror"
                        >{{ old('notes', $server->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
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

    @push('scripts')
    <script>
    function toggleAuthFields() {
        const authType = document.getElementById('auth_type').value;
        document.getElementById('credentials-password').style.display = authType === 'password' ? '' : 'none';
        document.getElementById('credentials-key').style.display = authType === 'key' ? '' : 'none';
    }
    document.getElementById('auth_type').addEventListener('change', toggleAuthFields);
    toggleAuthFields();
    </script>
    @endpush
</x-layouts.app>
