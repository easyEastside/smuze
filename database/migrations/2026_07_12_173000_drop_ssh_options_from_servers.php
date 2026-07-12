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
                'ssh_connect_timeout',
                'ssh_command_timeout',
                'ssh_control_persist',
                'ssh_server_alive_interval',
                'ssh_server_alive_count_max',
                'ssh_connection_attempts',
                'ssh_compression',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->integer('ssh_connect_timeout')->nullable()->default(5);
            $table->integer('ssh_command_timeout')->nullable()->default(30);
            $table->integer('ssh_control_persist')->nullable()->default(30);
            $table->integer('ssh_server_alive_interval')->nullable()->default(15);
            $table->integer('ssh_server_alive_count_max')->nullable()->default(3);
            $table->integer('ssh_connection_attempts')->nullable()->default(2);
            $table->boolean('ssh_compression')->nullable()->default(false);
        });
    }
};
