<?php

namespace App\Modules\Server\Backups\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/'],
            'type' => ['required', Rule::in(['mysql', 'files', 'both'])],
            'targets' => ['required', 'string'],
            'storage' => ['required', Rule::in(['local', 's3'])],
            's3_config' => ['nullable', 'array'],
            's3_config.bucket' => ['required_if:storage,s3', 'nullable', 'string', 'max:255'],
            's3_config.region' => ['required_if:storage,s3', 'nullable', 'string', 'max:64'],
            's3_config.endpoint' => ['nullable', 'string', 'max:255'],
            's3_config.access_key_id' => ['required_if:storage,s3', 'nullable', 'string', 'max:255'],
            's3_config.secret_access_key' => ['required_if:storage,s3', 'nullable', 'string', 'max:500'],
            'schedule' => ['nullable', 'string', 'max:120', 'not_regex:/[\r\n]/', function (string $attribute, mixed $value, callable $fail): void {
                if ($value !== null && ! $this->isValidSchedule((string) $value)) {
                    $fail('Der Zeitplan muss ein gültiger Cron-Ausdruck mit 5 Feldern sein.');
                }
            }],
            'enabled' => ['nullable', Rule::in(['1', '0', 1, 0, true, false])],
            'retention_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            's3_config.bucket.required_if' => 'Der S3-Bucket ist erforderlich bei S3-Speicher.',
            's3_config.region.required_if' => 'Die S3-Region ist erforderlich bei S3-Speicher.',
            's3_config.access_key_id.required_if' => 'Der S3-Access-Key-ID ist erforderlich bei S3-Speicher.',
            's3_config.secret_access_key.required_if' => 'Der S3-Secret-Access-Key ist erforderlich bei S3-Speicher.',
        ];
    }

    private function isValidSchedule(string $schedule): bool
    {
        $fields = preg_split('/\s+/', trim($schedule));

        if (count($fields) !== 5) {
            return false;
        }

        foreach ($fields as $field) {
            if (! preg_match('/^[A-Za-z0-9*\/,#?\-]+$/', $field)) {
                return false;
            }
        }

        return true;
    }
}
