<?php

namespace App\Services;

class AiSchemaValidator
{
    /**
     * Basic validation for required keys and simple type hints.
     *
     * @param array $schema JSON schema-like array (supports type, required, properties)
     * @param array $payload Decoded JSON payload
     * @return array list of error strings (empty when valid)
     */
    public function validate(array $schema, array $payload): array
    {
        $errors = [];
        $required = $schema['required'] ?? [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        $properties = $schema['properties'] ?? [];
        foreach ($properties as $name => $rules) {
            if (!array_key_exists($name, $payload)) {
                continue;
            }
            $val = $payload[$name];
            if (isset($rules['type'])) {
                $typeError = $this->checkType($rules['type'], $val);
                if ($typeError) {
                    $errors[] = "Field {$name}: {$typeError}";
                }
            }
        }

        return $errors;
    }

    private function checkType(string $type, mixed $val): ?string
    {
        return match ($type) {
            'string' => is_string($val) ? null : 'must be string',
            'array' => is_array($val) ? null : 'must be array',
            'object' => is_array($val) ? null : 'must be object/array',
            'integer' => is_int($val) ? null : 'must be integer',
            default => null,
        };
    }
}
