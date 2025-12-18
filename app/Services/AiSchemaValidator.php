<?php

namespace App\Services;

class AiSchemaValidator
{
    /**
     * Lightweight JSON-schema-like validation used as an AI guardrail.
     *
     * Supported keywords:
     * - type: object|array|string|integer|number|boolean
     * - required: string[] (object only)
     * - properties: { key: schema } (object only)
     * - items: schema (array only)
     * - enum: mixed[]
     * - maxLength/minLength: int (string only)
     *
     * @param array $schema JSON schema-like array (supports type, required, properties)
     * @param mixed $payload Decoded JSON payload
     * @return array list of error strings (empty when valid)
     */
    public function validate(array $schema, mixed $payload): array
    {
        $errors = [];
        $this->validateNode($schema, $payload, '$', $errors);

        return $errors;
    }

    private function validateNode(array $schema, mixed $value, string $path, array &$errors): void
    {
        if (array_key_exists('enum', $schema) && is_array($schema['enum'])) {
            if (!in_array($value, $schema['enum'], true)) {
                $errors[] = "{$path} must be one of: " . implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $schema['enum']));
                return;
            }
        }

        $type = $schema['type'] ?? null;
        if (is_string($type)) {
            $typeError = $this->checkType($type, $value);
            if ($typeError !== null) {
                $errors[] = "{$path} {$typeError}";
                return;
            }
        }

        if ($type === 'object') {
            $this->validateObject($schema, $value, $path, $errors);
            return;
        }

        if ($type === 'array') {
            $this->validateArray($schema, $value, $path, $errors);
            return;
        }

        if ($type === 'string') {
            $this->validateString($schema, $value, $path, $errors);
        }
    }

    private function validateObject(array $schema, mixed $value, string $path, array &$errors): void
    {
        if (!is_array($value) || array_is_list($value)) {
            $errors[] = "{$path} must be an object";
            return;
        }

        $required = $schema['required'] ?? [];
        if (is_array($required)) {
            foreach ($required as $field) {
                if (is_string($field) && !array_key_exists($field, $value)) {
                    $errors[] = "{$path}.{$field} is required";
                }
            }
        }

        $properties = $schema['properties'] ?? [];
        if (!is_array($properties)) {
            return;
        }

        foreach ($properties as $name => $childSchema) {
            if (!is_string($name) || !is_array($childSchema)) {
                continue;
            }
            if (!array_key_exists($name, $value)) {
                continue;
            }
            $this->validateNode($childSchema, $value[$name], "{$path}.{$name}", $errors);
        }
    }

    private function validateArray(array $schema, mixed $value, string $path, array &$errors): void
    {
        if (!is_array($value) || !array_is_list($value)) {
            $errors[] = "{$path} must be an array";
            return;
        }

        $itemsSchema = $schema['items'] ?? null;
        if (!is_array($itemsSchema)) {
            return;
        }

        foreach ($value as $index => $item) {
            $this->validateNode($itemsSchema, $item, "{$path}[{$index}]", $errors);
        }
    }

    private function validateString(array $schema, mixed $value, string $path, array &$errors): void
    {
        if (!is_string($value)) {
            return;
        }

        $len = mb_strlen($value);
        if (isset($schema['minLength']) && is_int($schema['minLength']) && $len < $schema['minLength']) {
            $errors[] = "{$path} must be at least {$schema['minLength']} characters";
        }
        if (isset($schema['maxLength']) && is_int($schema['maxLength']) && $len > $schema['maxLength']) {
            $errors[] = "{$path} must be at most {$schema['maxLength']} characters";
        }
    }

    private function checkType(string $type, mixed $val): ?string
    {
        if (is_null($val)) {
            return 'must not be null';
        }

        return match ($type) {
            'string' => is_string($val) ? null : 'must be a string',
            'array' => is_array($val) && array_is_list($val) ? null : 'must be an array',
            'object' => is_array($val) && !array_is_list($val) ? null : 'must be an object',
            'integer' => is_int($val) ? null : 'must be an integer',
            'number' => is_int($val) || is_float($val) ? null : 'must be a number',
            'boolean' => is_bool($val) ? null : 'must be a boolean',
            default => null,
        };
    }
}
