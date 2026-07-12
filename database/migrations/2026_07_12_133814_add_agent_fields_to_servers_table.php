<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->boolean('agent_enabled')->default(false)->after('ssh_compression');
            $table->text('agent_token')->nullable()->after('agent_enabled');
            $table->string('agent_version', 20)->nullable()->after('agent_token');
            $table->timestamp('agent_last_seen_at')->nullable()->after('agent_version');
            $table->string('agent_status', 20)->default('disconnected')->after('agent_last_seen_at')->index();
            $table->string('agent_transport', 20)->default('push')->after('agent_status');
            $table->string('execution_driver', 20)->default('ssh')->after('agent_transport');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->dropIndex(['agent_status']);
            $table->dropColumn([
                'agent_enabled',
                'agent_token',
                'agent_version',
                'agent_last_seen_at',
                'agent_status',
                'agent_transport',
                'execution_driver',
            ]);
        });
    }
};
