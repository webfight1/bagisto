<?php

namespace App\Services;

class EstoMacValidator
{
    public function isValid(array $payload, string $secret): bool
    {
        $mac = $payload['mac'] ?? null;
        // Esto sends 'json' field, not 'data'
        $data = $payload['json'] ?? $payload['data'] ?? null;

        if (! $mac || ! $data || ! $secret) {
            return false;
        }

        // Esto uses HMAC-SHA512 over the raw JSON string
        $expected = strtoupper(hash_hmac('sha512', $data, $secret));

        return hash_equals(strtoupper($mac), $expected);
    }
}
