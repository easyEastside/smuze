<?php

namespace App\Modules\Surveys\Controllers;

use App\Models\Survey;
use App\Modules\Surveys\Actions\StoreSurveyResponse;
use App\Modules\Surveys\Requests\StoreSurveyResponseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurveysController
{
    public function index(): View
    {
        $surveys = Survey::active()
            ->withCount('responses')
            ->orderByDesc('created_at')
            ->get();

        return view('modules.surveys.index', compact('surveys'));
    }

    public function show(Request $request, Survey $survey): View
    {
        abort_unless($survey->isAvailable(), 404);

        $survey->load('questions.options.answers');

        $hasResponded = $survey->wasAnsweredBy($request->user());
        $userResponse = $hasResponded
            ? $survey
                ->responses()
                ->whereBelongsTo($request->user())
                ->with('answers')
                ->first()
            : null;
        $userAnswerOptionIds = $userResponse?->answers->pluck('survey_option_id')->all() ?? [];

        return view('modules.surveys.show', compact('survey', 'hasResponded', 'userAnswerOptionIds'));
    }

    public function store(StoreSurveyResponseRequest $request, Survey $survey, StoreSurveyResponse $action): RedirectResponse
    {
        $action->handle(
            survey: $survey,
            user: $request->user(),
            answers: $request->validated('answers'),
        );

        return to_route('surveys.show', $survey)
            ->with('status', 'Thank you for taking the survey.');
    }
}
