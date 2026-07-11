<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_question_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->index(['survey_question_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_options');
    }
};
