<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->json('agent_metrics')->nullable()->after('agent_transport');
            $table->timestamp('agent_metrics_collected_at')->nullable()->after('agent_metrics');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->dropColumn(['agent_metrics', 'agent_metrics_collected_at']);
        });
    }
};
