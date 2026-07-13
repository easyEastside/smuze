<?php

use App\Models\Server;
use App\Models\ServerAgentCommand;
use App\Models\User;
use App\Services\ExecutionEngine\PushAgentEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('user can rotate server agent token', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create([
        'user_id' => $user->id,
        'agent_enabled' => false,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('server.agent.token', $server))
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'server_id' => $server->id,
        ]);

    $token = $response->json('token');
    $installCommand = $response->json('install_command');
    $server->refresh();
    $rawToken = DB::table('servers')->whereKey($server->id)->value('agent_token');

    expect($token)->toStartWith('smz_')
        ->and($server->agent_enabled)->toBeTrue()
        ->and($server->agent_token)->toBe($token)
        ->and($installCommand)->toContain('agent/download')
        ->and($installCommand)->toContain('smuze-agent')
        ->and($installCommand)->toContain('SUDO=""')
        ->and($installCommand)->toContain('$SUDO curl')
        ->and($installCommand)->toContain('--server-id')
        ->and($installCommand)->toContain((string) $server->id)
        ->and($installCommand)->toContain($token)
        ->and($rawToken)->not->toBe($token)
        ->and($server->agent_status)->toBe('disconnected');
});

test('user can disable server agent', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('server.agent.disable', $server))
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $server->refresh();

    expect($server->agent_enabled)->toBeFalse()
        ->and($server->agent_token)->toBeNull()
        ->and($server->agent_status)->toBe('disconnected');
});

test('user cannot manage another users server agent', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create();

    $this->actingAs($user)
        ->postJson(route('server.agent.token', $server))
        ->assertForbidden();
});

test('user can generate manual agent install command', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create([
        'user_id' => $user->id,
        'agent_enabled' => false,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('server.agent.install', $server))
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Install-Kommando generiert. Führe es manuell auf dem Server aus.',
        ]);

    $server->refresh();

    expect($server->agent_enabled)->toBeTrue()
        ->and($server->agent_token)->toStartWith('smz_')
        ->and($server->agent_status)->toBe('disconnected')
        ->and($response->json('install_command'))->toContain('agent/download')
        ->and($response->json('install_command'))->toContain('smuze-agent')
        ->and($response->json('install_command'))->toContain('$SUDO systemctl enable --now smuze-agent');
});

test('user can create terminal websocket token for their server', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('server.agent.terminal-token', $server))
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $url = $response->json('url');
    $query = parse_url($url, PHP_URL_QUERY);
    parse_str($query, $params);

    [$payload, $signature] = explode('.', $params['token']);
    $expectedSignature = rtrim(strtr(base64_encode(hash_hmac('sha256', $payload, $server->agent_token, true)), '+/', '-_'), '=');
    $decodedPayload = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

    expect($url)->toStartWith('ws://127.0.0.1:9300/terminal?token=')
        ->and($signature)->toBe($expectedSignature)
        ->and($decodedPayload['server_id'])->toBe($server->id)
        ->and($decodedPayload['purpose'])->toBe('terminal')
        ->and($decodedPayload['exp'])->toBeGreaterThan(now()->getTimestamp());
});

test('terminal websocket token uses agent public url when configured', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
        'agent_public_url' => 'https://agent.example.com/ws',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('server.agent.terminal-token', $server))
        ->assertSuccessful();

    expect($response->json('url'))->toStartWith('wss://agent.example.com/ws/terminal?token=');
});

test('user cannot create terminal websocket token for another users server', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => User::factory(),
    ]);

    $this->actingAs($user)
        ->getJson(route('server.agent.terminal-token', $server))
        ->assertForbidden();
});

test('agent download endpoint serves binary', function () {
    $this->get('/agent/download')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/octet-stream');
});

test('user cannot install agent on another users server', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create(['agent_enabled' => false]);

    $this->actingAs($user)
        ->postJson(route('server.agent.install', $server))
        ->assertForbidden();
});

test('agent check-update returns no update when versions match', function () {
    $saved = saveVersionFile();
    writeVersionFile('0.1.0', 'testchecksum123');

    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'agent_version' => '0.1.0',
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    config()->set('agent.latest_version', '0.1.0');
    Http::fake([
        'http://127.0.0.1:9300/health' => Http::response([
            'status' => 'ok',
            'version' => '0.1.0',
        ]),
    ]);

    $this->actingAs($user)
        ->getJson(route('server.agent.check-update', $server))
        ->assertSuccessful()
        ->assertJson([
            'has_update' => false,
            'current_version' => '0.1.0',
            'latest_version' => '0.1.0',
            'checksum' => 'testchecksum123',
        ]);

    restoreVersionFile($saved);
});

test('agent check-update returns update when newer version available', function () {
    $saved = saveVersionFile();
    writeVersionFile('0.2.0', 'testchecksum123');
    config()->set('agent.latest_version', '0.2.0');

    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'agent_version' => '0.1.0',
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/health' => Http::response([
            'status' => 'ok',
            'version' => '0.1.0',
        ]),
    ]);

    $this->actingAs($user)
        ->getJson(route('server.agent.check-update', $server))
        ->assertSuccessful()
        ->assertJson([
            'has_update' => true,
            'current_version' => '0.1.0',
            'latest_version' => '0.2.0',
            'checksum' => 'testchecksum123',
        ]);

    restoreVersionFile($saved);
});

test('agent check-update refreshes current version from health endpoint', function () {
    $saved = saveVersionFile();
    writeVersionFile('0.2.2', 'checksum-current');

    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'agent_version' => '0.1.0',
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/health' => Http::response([
            'status' => 'ok',
            'version' => '0.2.2',
        ]),
    ]);

    $this->actingAs($user)
        ->getJson(route('server.agent.check-update', $server))
        ->assertSuccessful()
        ->assertJson([
            'has_update' => false,
            'current_version' => '0.2.2',
            'latest_version' => '0.2.2',
        ]);

    expect($server->refresh()->agent_version)->toBe('0.2.2')
        ->and($server->agent_status)->toBe('connected')
        ->and($server->agent_last_seen_at)->not->toBeNull();

    restoreVersionFile($saved);
});

test('agent version endpoint returns release metadata', function () {
    $saved = saveVersionFile();
    writeVersionFile('0.3.0', 'checksum456');

    $this->getJson('/agent/version')
        ->assertSuccessful()
        ->assertJson([
            'latest_version' => '0.3.0',
            'checksum' => 'checksum456',
        ])
        ->assertJsonPath('download_url', url('/agent/download'));

    restoreVersionFile($saved);
});

test('agent update endpoint triggers update via push', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
    ]);

    $agent = Mockery::mock(PushAgentEngine::class);
    $agent->shouldReceive('triggerUpdate')
        ->once()
        ->with(Mockery::on(fn ($s) => $s->is($server)))
        ->andReturn(['success' => true, 'message' => 'Agent-Update gestartet.']);
    $this->app->instance(PushAgentEngine::class, $agent);

    $this->actingAs($user)
        ->postJson(route('server.agent.update', $server))
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Agent-Update gestartet.',
        ]);
});

test('agent engine sends release metadata when triggering update', function () {
    $saved = saveVersionFile();
    writeVersionFile('0.4.0', 'checksum789');

    $server = Server::factory()->withAgent()->create([
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/update' => Http::response([
            'success' => true,
            'message' => 'Update started',
        ]),
    ]);

    $result = app(PushAgentEngine::class)->triggerUpdate($server);

    expect($result)->toBe([
        'success' => true,
        'message' => 'Agent-Update gestartet.',
    ]);

    Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:9300/update'
        && $request['latest_version'] === '0.4.0'
        && $request['download_url'] === url('/agent/download')
        && $request['checksum'] === 'checksum789');

    restoreVersionFile($saved);
});

test('agent update fails when agent is not enabled', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create([
        'user_id' => $user->id,
        'agent_enabled' => false,
    ]);

    $this->actingAs($user)
        ->postJson(route('server.agent.update', $server))
        ->assertSuccessful()
        ->assertJson([
            'success' => false,
            'message' => 'Agent ist nicht aktiviert.',
        ]);
});

test('agent engine records command audit log', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/execute' => Http::response(json_encode(['stream' => 'stdout', 'data' => "ok\n"])."\n".json_encode(['done' => true, 'exit_code' => 0])."\n"),
    ]);

    $this->actingAs($user);

    $result = app(PushAgentEngine::class)->execute($server, 'whoami', timeout: 10, useSudo: false);

    expect($result->success)->toBeTrue()
        ->and(ServerAgentCommand::query()->count())->toBe(1);

    $command = ServerAgentCommand::query()->firstOrFail();

    expect($command->server_id)->toBe($server->id)
        ->and($command->user_id)->toBe($user->id)
        ->and($command->command)->toBe('whoami')
        ->and($command->timeout)->toBe(10)
        ->and($command->use_sudo)->toBeFalse()
        ->and($command->exit_code)->toBe(0)
        ->and($command->success)->toBeTrue()
        ->and($command->stdout)->toBe("ok\n");
});

test('guest cannot stream agent execute command', function () {
    $server = Server::factory()->withAgent()->create();

    $this->post(route('server.agent.execute.stream', $server), ['command' => 'whoami'])
        ->assertRedirect(route('login', absolute: false));
});

test('user cannot stream agent execute command on another users server', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => User::factory(),
    ]);

    $this->actingAs($user)
        ->post(route('server.agent.execute.stream', $server), ['command' => 'whoami'])
        ->assertForbidden();
});

test('agent execute stream proxies ndjson and records audit log', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/execute' => Http::response(json_encode(['stream' => 'stdout', 'data' => "one\n"])."\n".json_encode(['stream' => 'stderr', 'data' => "warn\n"])."\n".json_encode(['done' => true, 'exit_code' => 0])."\n"),
    ]);

    $response = $this->actingAs($user)
        ->post(route('server.agent.execute.stream', $server), [
            'command' => 'printf one',
            'timeout' => 10,
            'use_sudo' => false,
        ])
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/x-ndjson');

    expect($response->streamedContent())
        ->toContain('"stream":"stdout"')
        ->toContain('one\\n')
        ->toContain('"stream":"stderr"')
        ->toContain('warn\\n')
        ->toContain('"done":true')
        ->and(ServerAgentCommand::query()->count())->toBe(1);

    $command = ServerAgentCommand::query()->firstOrFail();

    expect($command->server_id)->toBe($server->id)
        ->and($command->user_id)->toBe($user->id)
        ->and($command->source)->toBe('proxy')
        ->and($command->command)->toBe('printf one')
        ->and($command->timeout)->toBe(10)
        ->and($command->use_sudo)->toBeFalse()
        ->and($command->exit_code)->toBe(0)
        ->and($command->success)->toBeTrue()
        ->and($command->stdout)->toBe("one\n")
        ->and($command->stderr)->toBe("warn\n");
});

test('agent execute stream forwards input to agent', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/execute' => Http::response(json_encode(['done' => true, 'exit_code' => 0])."\n"),
    ]);

    $response = $this->actingAs($user)
        ->post(route('server.agent.execute.stream', $server), [
            'command' => 'read -r line; echo "$line"',
            'timeout' => 10,
            'use_sudo' => false,
            'input' => "hello\n",
        ])
        ->assertSuccessful();

    $content = $response->streamedContent();

    expect($content)->toContain('"done":true');
});

test('agent execute stream rejects oversized input', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('server.agent.execute.stream', $server), [
            'command' => 'echo ok',
            'input' => str_repeat('a', 16001),
        ])
        ->assertSessionHasErrors('input');
});

test('agent engine records action audit log', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::response([
            'success' => true,
            'action' => 'system.apt_update',
            'exit_code' => 0,
            'stdout' => 'ok',
            'stderr' => '',
            'duration_ms' => 10,
        ]),
    ]);

    $this->actingAs($user);

    $result = app(PushAgentEngine::class)->action($server, 'system.apt_update');

    expect($result->success)->toBeTrue()
        ->and(ServerAgentCommand::query()->count())->toBe(1);

    $command = ServerAgentCommand::query()->firstOrFail();

    expect($command->server_id)->toBe($server->id)
        ->and($command->user_id)->toBe($user->id)
        ->and($command->source)->toBe('action')
        ->and($command->action)->toBe('system.apt_update')
        ->and($command->command)->toBeNull()
        ->and($command->exit_code)->toBe(0)
        ->and($command->success)->toBeTrue()
        ->and($command->stdout)->toBe('ok');
});

test('agent action 404 asks user to update agent', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::response(['error' => 'unknown action'], 404),
    ]);

    $this->actingAs($user);

    $result = app(PushAgentEngine::class)->action($server, 'services.install', ['service' => 'php']);

    expect($result->success)->toBeFalse()
        ->and($result->stderr)->toBe("Agent kennt die Action 'services.install' nicht. Bitte Agent aktualisieren.");
});

test('user can trigger whitelisted agent action', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::response([
            'success' => true,
            'action' => 'system.apt_update',
            'exit_code' => 0,
            'stdout' => 'updated',
            'stderr' => '',
            'duration_ms' => 10,
        ]),
    ]);

    $this->actingAs($user)
        ->postJson(route('server.agent.action', $server), [
            'action' => 'system.apt_update',
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'action' => 'system.apt_update',
            'exit_code' => 0,
            'stdout' => 'updated',
        ]);
});

test('user can trigger every system page agent action', function (string $action) {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::response([
            'success' => true,
            'action' => $action,
            'exit_code' => 0,
            'stdout' => 'ok',
            'stderr' => '',
            'duration_ms' => 10,
        ]),
    ]);

    $this->actingAs($user)
        ->postJson(route('server.agent.action', $server), [
            'action' => $action,
            'payload' => [],
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'action' => $action,
            'exit_code' => 0,
            'stdout' => 'ok',
        ]);
})->with([
    'apt update' => 'system.apt_update',
    'apt upgrade' => 'system.apt_upgrade',
    'reboot' => 'system.reboot',
    'shutdown' => 'system.shutdown',
]);
