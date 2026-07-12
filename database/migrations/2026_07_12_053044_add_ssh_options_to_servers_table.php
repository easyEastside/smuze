<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedSmallInteger('ssh_connect_timeout')->default(5)->after('key_content');
            $table->unsignedSmallInteger('ssh_command_timeout')->default(30)->after('ssh_connect_timeout');
            $table->unsignedSmallInteger('ssh_control_persist')->default(30)->after('ssh_command_timeout');
            $table->unsignedSmallInteger('ssh_server_alive_interval')->default(15)->after('ssh_control_persist');
            $table->unsignedTinyInteger('ssh_server_alive_count_max')->default(3)->after('ssh_server_alive_interval');
            $table->unsignedTinyInteger('ssh_connection_attempts')->default(2)->after('ssh_server_alive_count_max');
            $table->boolean('ssh_compression')->default(false)->after('ssh_connection_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
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
};
