<?php

namespace App\Support;

class ApiResponse
{
    /**
     * Build a successful envelope response.
     */
    public static function success(array $data = [], array $warnings = []): array
    {
        return [
            'success' => true,
            'data' => $data,
            'warnings' => $warnings,
            'errors' => [],
        ];
    }

    /**
     * Build a failure envelope response.
     *
     * @param array $errors Each error should include code/message and optional field.
     */
    public static function failure(array $errors, array $warnings = []): array
    {
        return [
            'success' => false,
            'data' => null,
            'warnings' => $warnings,
            'errors' => array_map(function ($err) {
                return [
                    'code' => $err['code'] ?? 'ERROR',
                    'message' => $err['message'] ?? 'Unknown error',
                    'field' => $err['field'] ?? null,
                ];
            }, $errors),
        ];
    }
}
