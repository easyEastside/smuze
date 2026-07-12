<?php

namespace App\Modules\Server\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'key_content' => ['nullable', 'string'],
            'key_path' => ['nullable', 'string', 'max:255'],
            'use_sudo' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
