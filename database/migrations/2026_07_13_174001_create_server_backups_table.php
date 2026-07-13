<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type'); // mysql, files, both
            $table->json('targets');
            $table->string('storage', 50)->default('local'); // local, s3
            $table->json('s3_config')->nullable();
            $table->string('schedule', 120)->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('retention_days')->default(7);
            $table->string('last_status', 20)->nullable(); // success, running, failed
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_backups');
    }
};
