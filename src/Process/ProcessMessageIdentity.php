<?php

declare(strict_types=1);

namespace Nexia\Process;

use InvalidArgumentException;

/** Builds collision-safe, deterministic identities for durable Process messages. */
final class ProcessMessageIdentity
{
    public const MAX_LENGTH = 190;

    private const SEGMENT_PATTERN = '[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?';

    private const SEGMENT_REGEX = '/^'.self::SEGMENT_PATTERN.'$/';

    private const NAMESPACE_PATTERN = '/^'.self::SEGMENT_PATTERN.'(?:\.'.self::SEGMENT_PATTERN.')+$/';

    private function __construct() {}

    public static function forEvent(string $appKey, string $consumerKey, string $eventIdentity): string
    {
        self::assertNamespaceSegment($appKey, 'appKey', false);
        self::assertNamespaceSegment($consumerKey, 'consumerKey', true);

        if ($eventIdentity === '' || $eventIdentity !== trim($eventIdentity) || preg_match('/\s/', $eventIdentity) === 1) {
            throw new InvalidArgumentException('Process message eventIdentity must be normalized, non-blank, and contain no whitespace.');
        }

        $identity = $appKey.'.'.$consumerKey.':'.$eventIdentity;
        self::assertValid($identity);

        return $identity;
    }

    public static function assertValid(string $identity): void
    {
        if ($identity === '' || $identity !== trim($identity)) {
            throw new InvalidArgumentException('Process message identity must be non-blank and normalized.');
        }
        if (strlen($identity) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Process message identity must not exceed '.self::MAX_LENGTH.' bytes.');
        }

        [$namespace, $eventIdentity] = array_pad(explode(':', $identity, 2), 2, null);
        if (! is_string($eventIdentity)
            || $eventIdentity === ''
            || preg_match(self::NAMESPACE_PATTERN, $namespace) !== 1
            || preg_match('/\s/', $eventIdentity) === 1) {
            throw new InvalidArgumentException('Process message identity must use the namespaced form [app.consumer:event-identity].');
        }
    }

    private static function assertNamespaceSegment(string $value, string $field, bool $nested): void
    {
        $pattern = $nested ? self::NAMESPACE_PATTERN : self::SEGMENT_REGEX;
        if ($value === '' || $value !== trim($value) || preg_match($pattern, $value) !== 1) {
            throw new InvalidArgumentException("Process message {$field} must be a normalized lowercase namespace segment.");
        }
    }
}
