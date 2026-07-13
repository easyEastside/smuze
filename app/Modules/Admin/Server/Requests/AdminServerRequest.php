<?php

namespace App\Modules\Admin\Server\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'agent_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'agent_public_url' => ['nullable', 'url:http,https', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
