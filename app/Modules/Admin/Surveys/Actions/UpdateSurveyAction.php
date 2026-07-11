<?php

namespace App\Modules\Admin\Surveys\Actions;

use App\Models\Survey;
use Illuminate\Support\Facades\DB;

class UpdateSurveyAction
{
    /** @param array<string, mixed> $validated */
    public function handle(Survey $survey, array $validated, bool $isActive): Survey
    {
        return DB::transaction(function () use ($survey, $validated, $isActive): Survey {
            $survey->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => $isActive,
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
            ]);

            $survey->questions()->delete();
            $this->storeQuestions($survey, $validated['questions']);

            return $survey;
        });
    }

    /** @param array<int, array<string, mixed>> $questions */
    protected function storeQuestions(Survey $survey, array $questions): void
    {
        foreach (array_values($questions) as $questionIndex => $questionData) {
            $question = $survey->questions()->create([
                'question' => $questionData['question'],
                'position' => $questionIndex + 1,
                'is_required' => true,
            ]);

            foreach (array_values($questionData['options']) as $optionIndex => $optionData) {
                $question->options()->create([
                    'label' => $optionData['label'],
                    'position' => $optionIndex + 1,
                ]);
            }
        }
    }
}
