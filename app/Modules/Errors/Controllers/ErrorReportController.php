<?php

namespace App\Modules\Errors\Controllers;

use App\Models\ErrorReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ErrorReportController
{
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('view', $request->user());

        $data = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'source' => ['nullable', 'string', 'max:100'],
            'details' => ['nullable', 'array'],
        ]);

        $report = ErrorReport::create([
            'user_id' => $request->user()?->id,
            'message' => $data['message'],
            'source' => $data['source'] ?? null,
            'details' => $data['details'] ?? null,
            'route' => $request->header('referer'),
        ]);

        return response()->json(['success' => true, 'id' => $report->id]);
    }
}
