<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_investments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('principal_amount');
            $table->integer('interest_amount');
            $table->decimal('base_hourly_rate', 5, 2);
            $table->unsignedTinyInteger('term_hours');
            $table->decimal('term_multiplier', 4, 2);
            $table->decimal('amount_multiplier', 4, 2);
            $table->timestamp('starts_at');
            $table->timestamp('matures_at');
            $table->timestamp('claimed_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('matures_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_investments');
    }
};
