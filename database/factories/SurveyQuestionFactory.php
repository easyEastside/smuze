<?php

namespace Database\Factories;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyQuestion>
 */
class SurveyQuestionFactory extends Factory
{
    protected $model = SurveyQuestion::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'question' => fake()->sentence().'?',
            'position' => 1,
            'is_required' => true,
        ];
    }
}
