<?php

declare(strict_types=1);

namespace Nexia\Laravel\Approval;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** HTTP adapter for the explicit Approval idempotency contract. */
final class ApprovalIdempotencyRequest
{
    public static function requireKey(Request $request, string $missingMessage): string
    {
        $raw = $request->header('Idempotency-Key');
        if ($raw === null) {
            throw ValidationException::withMessages(['idempotency_key' => [$missingMessage]]);
        }

        $key = trim((string) (is_array($raw) ? reset($raw) : $raw));
        if ($key === '') {
            throw ValidationException::withMessages([
                'idempotency_key' => [__('api.errors.idempotency_key_non_empty')],
            ]);
        }
        if (strlen($key) > 160) {
            throw ValidationException::withMessages([
                'idempotency_key' => [__('api.errors.idempotency_key_too_long')],
            ]);
        }

        return $key;
    }
}
