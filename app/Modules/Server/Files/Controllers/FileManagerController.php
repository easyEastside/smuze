<?php

namespace App\Modules\Server\Files\Controllers;

use App\Models\Server;
use App\Services\ExecutionEngine\ExecutionResult;
use App\Services\ExecutionEngine\PushAgentEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FileManagerController
{
    private const MAX_UPLOAD_BYTES = 25 * 1024 * 1024;

    public function __construct(
        private PushAgentEngine $agent,
    ) {}

    public function index(Server $server): View
    {
        Gate::authorize('update', $server);

        return view('modules.server.files.index', compact('server'));
    }

    public function list(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'path' => ['nullable', 'string', 'max:500', 'starts_with:/', 'not_regex:/[\r\n\x00]/', 'not_regex:/\.\./'],
        ]);

        return $this->jsonAction($server, 'files.list', ['path' => $data['path'] ?? '/var/www']);
    }

    public function read(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $this->validatePath($request);

        return $this->jsonAction($server, 'files.read', $data);
    }

    public function write(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'path' => ['required', 'string', 'max:500', 'starts_with:/', 'not_regex:/[\r\n\x00]/', 'not_regex:/\.\./'],
            'content' => ['present', 'string', 'max:1048576'],
        ]);

        return $this->jsonAction($server, 'files.write', $data);
    }

    public function createDirectory(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $this->validatePath($request);

        return $this->jsonAction($server, 'files.mkdir', $data);
    }

    public function createFile(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $this->validatePath($request);

        return $this->jsonAction($server, 'files.touch', $data);
    }

    public function rename(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'path' => ['required', 'string', 'max:500', 'starts_with:/', 'not_regex:/[\r\n\x00]/', 'not_regex:/\.\./'],
            'new_path' => ['required', 'string', 'max:500', 'starts_with:/', 'not_regex:/[\r\n\x00]/', 'not_regex:/\.\./'],
        ]);

        return $this->jsonAction($server, 'files.rename', $data);
    }

    public function chmod(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'path' => ['required', 'string', 'max:500', 'starts_with:/', 'not_regex:/[\r\n\x00]/', 'not_regex:/\.\./'],
            'mode' => ['required', 'string', 'regex:/^0?[0-7]{3,4}$/'],
        ]);

        return $this->jsonAction($server, 'files.chmod', $data);
    }

    public function delete(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'path' => ['required', 'string', 'max:500', 'starts_with:/', 'not_regex:/[\r\n\x00]/', 'not_regex:/\.\./'],
            'recursive' => ['nullable', 'boolean'],
        ]);

        return $this->jsonAction($server, 'files.delete', [
            'path' => $data['path'],
            'recursive' => $request->boolean('recursive'),
        ]);
    }

    public function upload(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'directory' => ['required', 'string', 'max:500', 'starts_with:/', 'not_regex:/[\r\n\x00]/', 'not_regex:/\.\./'],
            'file' => ['required', 'file', 'max:25600'],
        ]);

        $file = $request->file('file');
        abort_unless($file !== null && $file->isValid(), 422);
        abort_if($file->getSize() > self::MAX_UPLOAD_BYTES, 422);

        $filename = basename($file->getClientOriginalName());
        abort_if($filename === '' || $filename !== $file->getClientOriginalName(), 422, 'Dateiname ist ungültig.');
        abort_if(preg_match('/[\r\n\x00]/', $filename) === 1 || str_contains($filename, '..'), 422, 'Dateiname ist ungültig.');

        return $this->jsonAction($server, 'files.upload', [
            'path' => rtrim($data['directory'], '/').'/'.$filename,
            'content_base64' => base64_encode($file->getContent()),
        ]);
    }

    public function download(Request $request, Server $server): Response
    {
        Gate::authorize('update', $server);

        $data = $this->validatePath($request);
        $result = $this->agent->action($server, 'files.download', $data);

        abort_unless($result->success, 422, $result->stderr ?: 'Download fehlgeschlagen.');

        $payload = json_decode($result->stdout, true) ?: [];
        $content = base64_decode((string) ($payload['content_base64'] ?? ''), true);

        abort_if($content === false, 422, 'Download konnte nicht dekodiert werden.');

        $filename = basename((string) ($payload['path'] ?? $data['path']));

        return response($content, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.Str::ascii($filename).'"',
        ]);
    }

    /** @return array{path: string} */
    private function validatePath(Request $request): array
    {
        /** @var array{path: string} $data */
        $data = $request->validate([
            'path' => ['required', 'string', 'max:500', 'starts_with:/', 'not_regex:/[\r\n\x00]/', 'not_regex:/\.\./'],
        ]);

        return $data;
    }

    /** @param array<string, mixed> $payload */
    private function jsonAction(Server $server, string $action, array $payload): JsonResponse
    {
        $result = $this->agent->action($server, $action, $payload);

        return response()->json([
            'success' => $result->success,
            'data' => $this->decodeStdout($result),
            'stdout' => $result->stdout,
            'stderr' => $result->stderr,
            'error' => $result->success ? null : ($result->stderr ?: 'Datei-Aktion fehlgeschlagen.'),
        ], $result->success ? 200 : 422);
    }

    private function decodeStdout(ExecutionResult $result): mixed
    {
        if (! $result->success || $result->stdout === '') {
            return null;
        }

        return json_decode($result->stdout, true) ?: null;
    }
}
