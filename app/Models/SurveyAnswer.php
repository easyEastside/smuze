<?php

namespace App\Models;

use Database\Factories\SurveyAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyAnswer extends Model
{
    /** @use HasFactory<SurveyAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_response_id',
        'survey_question_id',
        'survey_option_id',
    ];

    /** @return BelongsTo<SurveyResponse, $this> */
    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    /** @return BelongsTo<SurveyQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }

    /** @return BelongsTo<SurveyOption, $this> */
    public function option(): BelongsTo
    {
        return $this->belongsTo(SurveyOption::class, 'survey_option_id');
    }
}
