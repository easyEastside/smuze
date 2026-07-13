<?php

namespace App\Modules\Admin\Errors\Controllers;

use App\Models\ErrorReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminErrorReportsController
{
    public function index(): View
    {
        $reports = ErrorReport::with('user:id,name')
            ->latest()
            ->paginate(30);

        return view('modules.admin.errors.index', compact('reports'));
    }

    public function show(ErrorReport $errorReport): View
    {
        $errorReport->load('user:id,name');

        return view('modules.admin.errors.show', compact('errorReport'));
    }

    public function destroy(ErrorReport $errorReport): RedirectResponse
    {
        $errorReport->delete();

        return to_route('admin.errors')
            ->with('flash', ['success' => 'Fehlerbericht gelöscht.']);
    }
}
