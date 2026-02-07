<?php

namespace App\Services;

class EstoMacValidator
{
    public function isValid(array $payload, string $secret): bool
    {
        $mac = $payload['mac'] ?? null;
        $data = $payload['data'] ?? null;

        if (! $mac || ! $data || ! $secret) {
            return false;
        }

        // Esto uses HMAC-SHA256 over the raw data JSON string
        $expected = hash_hmac('sha256', $data, $secret);

        return hash_equals($expected, $mac);
    }
}
