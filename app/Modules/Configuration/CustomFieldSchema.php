<?php

namespace App\Modules\Configuration;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomFieldSchema
{
    public const TYPES = ['text', 'integer', 'boolean', 'date', 'select'];

    /** @param array<int, mixed> $definitions
     * @return list<array<string, mixed>>
     */
    public function definitions(array $definitions): array
    {
        $data = Validator::make(['fields' => $definitions], [
            'fields' => ['array', 'list', 'max:20'],
            'fields.*' => ['required', 'array:key,entity,type,label,required,options'],
            'fields.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{2,39}$/', 'distinct:strict', 'not_in:id,tenant_id,name,email,phone,source,status,notes,version,created_by,created_at,updated_at'],
            'fields.*.entity' => ['required', 'in:lead'],
            'fields.*.type' => ['required', 'in:'.implode(',', self::TYPES)],
            'fields.*.label' => ['required', 'array:en,bn'],
            'fields.*.label.en' => ['required', 'string', 'max:60'],
            'fields.*.label.bn' => ['required', 'string', 'max:60'],
            'fields.*.required' => ['required', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_bool($value)) {
                    $fail('Required must be a JSON boolean.');
                }
            }],
            'fields.*.options' => ['sometimes', 'array', 'list', 'min:1', 'max:30'],
            'fields.*.options.*' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,39}$/'],
        ])->validate();
        foreach ($data['fields'] as $index => $field) {
            $options = $field['options'] ?? null;
            if (($field['type'] === 'select' && (! is_array($options) || count(array_unique($options)) !== count($options)))
                || ($field['type'] !== 'select' && $options !== null)) {
                throw ValidationException::withMessages(['fields.'.$index.'.options' => 'Only select fields require unique allowed options.']);
            }
        }

        return $data['fields'];
    }

    /** Pure validation foundation: does not persist or activate custom values.
     * @param  array<int, mixed>  $definitions
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function values(array $definitions, array $values): array
    {
        $fields = $this->definitions($definitions);
        $allowed = array_column($fields, 'key');
        foreach (array_keys($values) as $key) {
            if (! in_array($key, $allowed, true)) {
                throw ValidationException::withMessages(['values' => 'Unknown custom field.']);
            }
        }
        foreach ($fields as $field) {
            $key = $field['key'];
            $value = $values[$key] ?? null;
            if ($value === null || $value === '') {
                if ($field['required']) {
                    throw ValidationException::withMessages(['values.'.$key => 'This field is required.']);
                }

                continue;
            }
            $valid = match ($field['type']) {
                'text' => is_string($value) && mb_strlen($value) <= 500,
                'integer' => is_int($value) && $value >= -2147483648 && $value <= 2147483647,
                'boolean' => is_bool($value),
                'date' => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) === 1
                    && ($date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value)) !== false && $date->format('Y-m-d') === $value,
                'select' => is_string($value) && in_array($value, $field['options'], true),
                default => false,
            };
            if (! $valid) {
                throw ValidationException::withMessages(['values.'.$key => 'Value does not match its field type or limits.']);
            }
        }

        return $values;
    }
}
