<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->dropColumn([
                'port',
                'username',
                'auth_type',
                'credentials',
                'use_sudo',
                'key_path',
                'key_content',
                'agent_transport',
                'execution_driver',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->integer('port')->default(22)->after('host');
            $table->string('username')->after('port');
            $table->string('auth_type')->after('username');
            $table->text('credentials')->nullable()->after('auth_type');
            $table->boolean('use_sudo')->default(true)->after('credentials');
            $table->string('key_path')->nullable()->after('use_sudo');
            $table->text('key_content')->nullable()->after('key_path');
            $table->string('agent_transport', 20)->default('push')->after('agent_status');
            $table->string('execution_driver', 20)->default('ssh')->after('agent_transport');
        });
    }
};
