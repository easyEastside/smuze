<?php

namespace Database\Factories;

use App\Models\SurveyAnswer;
use App\Models\SurveyOption;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyAnswer>
 */
class SurveyAnswerFactory extends Factory
{
    protected $model = SurveyAnswer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $question = SurveyQuestion::factory();

        return [
            'survey_response_id' => SurveyResponse::factory(),
            'survey_question_id' => $question,
            'survey_option_id' => SurveyOption::factory()->for($question, 'question'),
        ];
    }
}
