<?php

namespace App\Modules\Server\Mysql\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_][A-Za-z0-9_.-]{0,31}$/'],
            'host' => ['required', 'string', 'max:255', 'regex:/^(%|localhost|[A-Za-z0-9](?:[A-Za-z0-9.\-]{0,251}[A-Za-z0-9])?)$/', 'not_regex:/\.\./'],
            'password' => ['required', 'string', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'username.required' => 'Benutzername ist erforderlich.',
            'username.regex' => 'Benutzername darf nur Buchstaben, Zahlen, Punkt, Unterstrich und Bindestrich enthalten.',
            'host.required' => 'Host ist erforderlich.',
            'host.regex' => 'Host muss localhost, % oder ein gültiger DNS-Name sein.',
            'password.required' => 'Passwort ist erforderlich.',
        ];
    }
}
