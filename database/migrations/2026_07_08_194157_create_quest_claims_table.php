<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quest_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('quest_key');
            $table->integer('reward_credits');
            $table->date('claimed_for_date');
            $table->timestamp('claimed_at');
            $table->timestamps();

            $table->unique(['user_id', 'quest_key', 'claimed_for_date']);
            $table->index(['quest_key', 'claimed_for_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quest_claims');
    }
};
