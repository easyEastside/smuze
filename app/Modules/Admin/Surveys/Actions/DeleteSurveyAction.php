<?php

namespace App\Modules\Admin\Surveys\Actions;

use App\Models\Survey;

class DeleteSurveyAction
{
    public function handle(Survey $survey): void
    {
        $survey->delete();
    }
}
