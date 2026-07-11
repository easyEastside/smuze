<?php

namespace App\Modules\Surveys\Actions;

use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Modules\Achievements\Actions\UnlockAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreSurveyResponse
{
    /**
     * @param  array<string, int>  $answers
     *
     * @throws ValidationException
     */
    public function handle(Survey $survey, User $user, array $answers): SurveyResponse
    {
        $survey->load('questions.options');

        if (! $survey->isAvailable()) {
            throw ValidationException::withMessages(['survey' => 'This survey is not available.']);
        }

        if ($survey->wasAnsweredBy($user)) {
            throw ValidationException::withMessages(['survey' => 'You have already taken this survey.']);
        }

        foreach ($survey->questions as $question) {
            $optionId = $answers[$question->id] ?? null;
            $validOptionIds = $question->options->pluck('id');

            if ($question->is_required && $optionId === null) {
                throw ValidationException::withMessages(["answers.{$question->id}" => 'Please answer this question.']);
            }

            if ($optionId !== null && ! $validOptionIds->contains((int) $optionId)) {
                throw ValidationException::withMessages(["answers.{$question->id}" => 'Please select a valid answer.']);
            }
        }

        return DB::transaction(function () use ($survey, $user, $answers): SurveyResponse {
            $response = SurveyResponse::create([
                'survey_id' => $survey->id,
                'user_id' => $user->id,
            ]);

            foreach ($survey->questions as $question) {
                if (! isset($answers[$question->id])) {
                    continue;
                }

                $response->answers()->create([
                    'survey_question_id' => $question->id,
                    'survey_option_id' => (int) $answers[$question->id],
                ]);
            }

            app(UnlockAchievement::class)->handle($user, 'survey_taker');

            if (SurveyResponse::query()->where('user_id', $user->id)->count() >= 10) {
                app(UnlockAchievement::class)->handle($user, 'survey_master');
            }

            return $response;
        });
    }
}
