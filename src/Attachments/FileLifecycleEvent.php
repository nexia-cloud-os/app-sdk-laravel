<?php

declare(strict_types=1);

namespace Nexia\Attachments;

use InvalidArgumentException;

/** A platform-neutral file lifecycle fact. Context is scalar and allowlisted by the host. */
final readonly class FileLifecycleEvent
{
    /** @var list<string> */
    public const OPERATIONS = [
        'intake', 'received', 'verified', 'scanned', 'consumed', 'attached', 'generated',
        'transformed', 'published', 'internal_read', 'preview', 'download', 'url_issued',
        'provider_handoff', 'disposition', 'cancelled', 'inventory', 'retention',
    ];

    /** @var list<string> */
    public const OUTCOMES = [
        'started', 'succeeded', 'failed', 'rejected', 'pending', 'completed', 'cancelled',
        'quarantined', 'expired', 'observed', 'missing', 'unknown',
    ];

    /** @param array<string, scalar|null> $context */
    public function __construct(
        public string $operation,
        public string $outcome,
        public FileLifecycleSubject $subject,
        public array $context = [],
        public ?string $dedupeKey = null,
    ) {
        if (! in_array($operation, self::OPERATIONS, true) || ! in_array($outcome, self::OUTCOMES, true)) {
            throw new InvalidArgumentException('File lifecycle operation or outcome is invalid.');
        }
        if ($dedupeKey !== null && ($dedupeKey === '' || mb_strlen($dedupeKey) > 191)) {
            throw new InvalidArgumentException('File lifecycle dedupe key is invalid.');
        }
    }
}
