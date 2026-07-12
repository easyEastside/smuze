<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('show_floating_terminal')->default(true)->after('credits');
            $table->boolean('write_debug_logs')->default(true)->after('show_floating_terminal');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['show_floating_terminal', 'write_debug_logs']);
        });
    }
};
