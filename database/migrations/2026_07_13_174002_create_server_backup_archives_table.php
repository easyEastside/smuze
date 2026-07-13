<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_backup_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_backup_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('type'); // mysql, files, both
            $table->bigInteger('size_bytes')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('storage', 50)->default('local'); // local, s3
            $table->string('status', 20)->default('pending'); // pending, running, success, failed
            $table->integer('exit_code')->nullable();
            $table->longText('output')->nullable();
            $table->longText('error_output')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['server_backup_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_backup_archives');
    }
};
