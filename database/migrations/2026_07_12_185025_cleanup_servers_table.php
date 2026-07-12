<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['status', 'agent_metrics', 'agent_metrics_collected_at']);
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('status')->default('unknown')->after('notes');
            $table->json('agent_metrics')->nullable()->after('agent_port');
            $table->timestamp('agent_metrics_collected_at')->nullable()->after('agent_metrics');
        });
    }
};
