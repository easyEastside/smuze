<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host');
            $table->integer('port')->default(22);
            $table->string('username');
            $table->string('auth_type'); // password, key
            $table->text('credentials')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('unknown'); // online, offline, unknown
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
