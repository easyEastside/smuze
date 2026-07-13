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
        Schema::create('server_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('repo_url', 500);
            $table->string('target_path');
            $table->string('domain', 253)->nullable();
            $table->string('webserver', 20)->default('none');
            $table->string('php_version', 10)->default('8.5');
            $table->boolean('install_node')->default(false);
            $table->boolean('run_build')->default(false);
            $table->boolean('run_migrations')->default(false);
            $table->boolean('write_env')->default(true);
            $table->json('env')->nullable();
            $table->string('last_status', 20)->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'last_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_deployments');
    }
};
