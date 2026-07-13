<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('cpu_percent')->nullable();
            $table->unsignedSmallInteger('ram_percent')->nullable();
            $table->unsignedInteger('ram_used_mb')->nullable();
            $table->unsignedInteger('ram_total_mb')->nullable();
            $table->unsignedSmallInteger('disk_percent')->nullable();
            $table->unsignedInteger('disk_used_mb')->nullable();
            $table->unsignedInteger('disk_total_mb')->nullable();
            $table->string('load', 50)->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_metrics');
    }
};
