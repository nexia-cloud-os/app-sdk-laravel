<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

/** A reportable failure handled inside an App write request. */
final readonly class OperationFailure
{
    public function __construct(
        private string $stage,
        private string $code,
        private ?int $count = null,
        private ?int $httpStatus = null,
    ) {}

    /**
     * Attach diagnostics to an object-shaped JSON response without changing its business result.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    public static function with(array $response, string $operation, self ...$failures): array
    {
        if ($failures === [] || array_is_list($response) || ! self::validIdentifier($operation)
            || array_key_exists('__nexia_operation_failure', $response)) {
            return $response;
        }

        $reasons = [];
        foreach ($failures as $failure) {
            if (! self::validIdentifier($failure->stage) || ! self::validIdentifier($failure->code)) {
                continue;
            }

            $reason = ['stage' => $failure->stage, 'code' => $failure->code];
            if ($failure->count !== null && $failure->count >= 1 && $failure->count <= 1000000) {
                $reason['count'] = $failure->count;
            }
            if ($failure->httpStatus !== null && $failure->httpStatus >= 400 && $failure->httpStatus <= 599) {
                $reason['http_status'] = $failure->httpStatus;
            }
            $reasons[] = $reason;
            if (count($reasons) === 8) {
                break;
            }
        }

        if ($reasons !== []) {
            $response['__nexia_operation_failure'] = ['operation' => $operation, 'reasons' => $reasons];
        }

        return $response;
    }

    private static function validIdentifier(string $value): bool
    {
        return preg_match('/\A[a-zA-Z][a-zA-Z0-9_.-]{0,99}\z/D', $value) === 1;
    }
}
