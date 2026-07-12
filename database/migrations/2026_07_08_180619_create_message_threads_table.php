<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('participant_two_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->timestamps();

            $table->index(['participant_one_id', 'updated_at']);
            $table->index(['participant_two_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_threads');
    }
};
