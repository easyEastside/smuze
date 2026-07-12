<?php

namespace App\Modules\Server\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServerRequest extends FormRequest
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
            'agent_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
