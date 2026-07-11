<?php

namespace App\Models;

use Database\Factories\SurveyResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyResponse extends Model
{
    /** @use HasFactory<SurveyResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'user_id',
    ];

    /** @return BelongsTo<Survey, $this> */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<SurveyAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }
}
