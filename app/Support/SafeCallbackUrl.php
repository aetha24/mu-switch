<?php

namespace App\Support;

/** Guard merchant callback delivery against SSRF and accidental plain HTTP. */
final class SafeCallbackUrl
{
    public static function isAllowed(mixed $value): bool
    {
        if (! is_string($value) || strlen($value) > 2048) {
            return false;
        }

        $parts = parse_url($value);
        if (! is_array($parts)
            || ($parts['scheme'] ?? null) !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])) {
            return false;
        }

        $host = strtolower(trim((string) $parts['host'], '[]'));
        if (in_array($host, ['localhost', 'metadata.google.internal'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local')) {
            return false;
        }

        // Literal addresses are deterministic and can be rejected safely. For
        // host names, production egress controls remain the final layer because
        // DNS can change between validation and connection.
        if (filter_var($host, FILTER_VALIDATE_IP)
            && ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        return true;
    }
}
