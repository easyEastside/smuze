<?php

namespace App\Modules\Server\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ssh_connect_timeout' => $this->input('ssh_connect_timeout', 5),
            'ssh_command_timeout' => $this->input('ssh_command_timeout', 30),
            'ssh_control_persist' => $this->input('ssh_control_persist', 30),
            'ssh_server_alive_interval' => $this->input('ssh_server_alive_interval', 15),
            'ssh_server_alive_count_max' => $this->input('ssh_server_alive_count_max', 3),
            'ssh_connection_attempts' => $this->input('ssh_connection_attempts', 2),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'auth_type' => ['required', 'string', 'in:password,key'],
            'credentials' => ['nullable', 'string'],
            'key_path' => ['nullable', 'string', 'max:255'],
            'key_content' => ['nullable', 'string'],
            'use_sudo' => ['boolean'],
            'install_agent' => ['boolean'],
            'ssh_connect_timeout' => ['required', 'integer', 'min:1', 'max:300'],
            'ssh_command_timeout' => ['required', 'integer', 'min:1', 'max:3600'],
            'ssh_control_persist' => ['required', 'integer', 'min:1', 'max:1440'],
            'ssh_server_alive_interval' => ['required', 'integer', 'min:1', 'max:300'],
            'ssh_server_alive_count_max' => ['required', 'integer', 'min:1', 'max:10'],
            'ssh_connection_attempts' => ['required', 'integer', 'min:1', 'max:5'],
            'ssh_compression' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
