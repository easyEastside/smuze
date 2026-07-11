<?php

namespace App\Modules\Admin\Surveys\Controllers;

use App\Models\Survey;
use App\Modules\Admin\Surveys\Actions\DeleteSurveyAction;
use App\Modules\Admin\Surveys\Actions\StoreSurveyAction;
use App\Modules\Admin\Surveys\Actions\UpdateSurveyAction;
use App\Modules\Admin\Surveys\Requests\StoreSurveyRequest;
use App\Modules\Admin\Surveys\Requests\UpdateSurveyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminSurveysController
{
    public function index(): View
    {
        $surveys = Survey::withCount(['questions', 'responses'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('modules.admin.surveys.index', compact('surveys'));
    }

    public function create(): View
    {
        return view('modules.admin.surveys.create');
    }

    public function store(StoreSurveyRequest $request, StoreSurveyAction $action): RedirectResponse
    {
        $survey = $action->handle(
            validated: $request->validated(),
            isActive: $request->boolean('is_active', true),
        );

        return to_route('admin.surveys.index')
            ->with('flash', ['success' => "Survey {$survey->title} created successfully."]);
    }

    public function show(Survey $survey): View
    {
        $survey->load([
            'questions.options.answers',
            'responses.user',
            'responses.answers.question',
            'responses.answers.option',
        ]);

        return view('modules.admin.surveys.show', compact('survey'));
    }

    public function edit(Survey $survey): View
    {
        $survey->load('questions.options');

        return view('modules.admin.surveys.edit', compact('survey'));
    }

    public function update(UpdateSurveyRequest $request, Survey $survey, UpdateSurveyAction $action): RedirectResponse
    {
        $action->handle(
            survey: $survey,
            validated: $request->validated(),
            isActive: $request->boolean('is_active', true),
        );

        return to_route('admin.surveys.index')
            ->with('flash', ['success' => "Survey {$survey->title} updated successfully."]);
    }

    public function destroy(Survey $survey, DeleteSurveyAction $action): RedirectResponse
    {
        $title = $survey->title;

        $action->handle($survey);

        return to_route('admin.surveys.index')
            ->with('flash', ['success' => "Survey {$title} deleted successfully."]);
    }
}
