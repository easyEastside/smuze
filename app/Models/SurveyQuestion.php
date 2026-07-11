<?php

namespace App\Models;

use Database\Factories\SurveyQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestion extends Model
{
    /** @use HasFactory<SurveyQuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'question',
        'position',
        'is_required',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    /** @return BelongsTo<Survey, $this> */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /** @return HasMany<SurveyOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(SurveyOption::class)->orderBy('position');
    }

    /** @return HasMany<SurveyAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }
}
