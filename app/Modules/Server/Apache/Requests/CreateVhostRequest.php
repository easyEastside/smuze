<?php

namespace App\Modules\Server\Apache\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateVhostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:253', 'regex:/^(?=.{1,253}$)[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?)*$/'],
            'document_root' => ['required', 'string', 'max:255', 'regex:/^\//', 'not_regex:/\.\./'],
            'server_alias' => ['sometimes', 'string', 'max:255'],
            'use_ssl' => ['sometimes', 'boolean'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'domain.required' => 'Die Domain ist erforderlich.',
            'domain.regex' => 'Die Domain darf nur gültige DNS-Zeichen enthalten, z. B. example.com.',
            'document_root.required' => 'Der DocumentRoot ist erforderlich.',
            'document_root.regex' => 'Der DocumentRoot muss ein absoluter Pfad sein, der mit / beginnt.',
            'document_root.not_regex' => 'Der DocumentRoot darf kein ".." enthalten.',
            'email.email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'use_ssl' => filter_var($this->input('use_ssl'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
        ]);
    }
}
