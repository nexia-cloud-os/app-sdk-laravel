<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/**
 * Typed provider outcome. Its protected resolved values stay behind explicit
 * accessors so they cannot accidentally enter a generic catalog or log array.
 */
final class SignatureDocumentDataResult
{
    /** @var list<SignatureDocumentDataCandidate> */
    private array $candidates;

    /** @var list<SignatureDocumentDataResolvedItem> */
    private array $resolvedItems;

    /** @var list<SignatureDocumentDataDiagnostic> */
    private array $diagnostics;

    /**
     * @param  list<SignatureDocumentDataCandidate>  $candidates
     * @param  list<SignatureDocumentDataResolvedItem>  $resolvedItems
     * @param  list<SignatureDocumentDataDiagnostic>  $diagnostics
     */
    public function __construct(
        public readonly SignatureDocumentDataStatus $status,
        array $candidates = [],
        array $resolvedItems = [],
        array $diagnostics = [],
    ) {
        if (! array_is_list($candidates) || ! array_is_list($resolvedItems) || ! array_is_list($diagnostics)) {
            throw new InvalidArgumentException('Signature document data result collections must be lists.');
        }
        if (count($candidates) > SignatureDocumentDataLimits::MAX_LIST_ITEMS
            || count($resolvedItems) > SignatureDocumentDataLimits::MAX_LIST_ITEMS
            || count($diagnostics) > 4) {
            throw new InvalidArgumentException('Signature document data result exceeds v1 collection bounds.');
        }

        $candidateRefs = [];
        foreach ($candidates as $candidate) {
            if (! $candidate instanceof SignatureDocumentDataCandidate) {
                throw new InvalidArgumentException('Signature document data result candidates must be candidate DTOs.');
            }
            $identity = $candidate->resourceRef->appKey."\0".$candidate->resourceRef->resourceKey."\0".$candidate->resourceRef->resourceId;
            if (isset($candidateRefs[$identity])) {
                throw new InvalidArgumentException('Signature document data result candidates must be unique resource references.');
            }
            $candidateRefs[$identity] = true;
        }

        $resolvedRefs = [];
        foreach ($resolvedItems as $item) {
            if (! $item instanceof SignatureDocumentDataResolvedItem) {
                throw new InvalidArgumentException('Signature document data result resolved items must be resolved-item DTOs.');
            }
            $identity = $item->resourceRef->appKey."\0".$item->resourceRef->resourceKey."\0".$item->resourceRef->resourceId;
            if (isset($resolvedRefs[$identity])) {
                throw new InvalidArgumentException('Signature document data result resolved items must be unique resource references.');
            }
            $resolvedRefs[$identity] = true;
        }

        $diagnosticCodes = [];
        foreach ($diagnostics as $diagnostic) {
            if (! $diagnostic instanceof SignatureDocumentDataDiagnostic || isset($diagnosticCodes[$diagnostic->code->value])) {
                throw new InvalidArgumentException('Signature document data result diagnostics must be unique bounded diagnostic DTOs.');
            }
            $diagnosticCodes[$diagnostic->code->value] = true;
        }

        match ($status) {
            SignatureDocumentDataStatus::Resolved => ($resolvedItems !== [] && $candidates === [])
                ?: throw new InvalidArgumentException('A resolved signature document data result requires resolved items and no candidates.'),
            SignatureDocumentDataStatus::SelectionRequired => ($candidates !== [] && $resolvedItems === [])
                ?: throw new InvalidArgumentException('A selection-required signature document data result requires candidates and no resolved items.'),
            SignatureDocumentDataStatus::Missing,
            SignatureDocumentDataStatus::Stale,
            SignatureDocumentDataStatus::Unavailable => ($candidates === [] && $resolvedItems === [])
                ?: throw new InvalidArgumentException('A non-resolved signature document data result must not expose candidates or protected values.'),
        };

        self::assertAggregatePayloadSize($candidates, $resolvedItems, $diagnostics);

        $this->candidates = $candidates;
        $this->resolvedItems = $resolvedItems;
        $this->diagnostics = $diagnostics;
    }

    /** @return list<SignatureDocumentDataCandidate> */
    public function candidates(): array
    {
        return $this->candidates;
    }

    /** @return list<SignatureDocumentDataResolvedItem> */
    public function resolvedItems(): array
    {
        return $this->resolvedItems;
    }

    /** @return list<SignatureDocumentDataDiagnostic> */
    public function diagnostics(): array
    {
        return $this->diagnostics;
    }

    /**
     * This private envelope exists only for bound checking. It is not a
     * serialization API: callers still retrieve protected values explicitly
     * from a resolved item when creating an encrypted host snapshot.
     *
     * @param  list<SignatureDocumentDataCandidate>  $candidates
     * @param  list<SignatureDocumentDataResolvedItem>  $resolvedItems
     * @param  list<SignatureDocumentDataDiagnostic>  $diagnostics
     */
    private static function assertAggregatePayloadSize(array $candidates, array $resolvedItems, array $diagnostics): void
    {
        SignatureDocumentDataLimits::assertPayloadByteLength(
            [
                'candidates' => array_map(
                    static fn (SignatureDocumentDataCandidate $candidate): array => $candidate->toArray(),
                    $candidates,
                ),
                'resolved_items' => array_map(
                    static fn (SignatureDocumentDataResolvedItem $item): array => [
                        'provenance' => $item->provenance(),
                        'values' => $item->protectedValues(),
                        'display_values' => $item->displayValues(),
                    ],
                    $resolvedItems,
                ),
                'diagnostics' => array_map(
                    static fn (SignatureDocumentDataDiagnostic $diagnostic): array => $diagnostic->toArray(),
                    $diagnostics,
                ),
            ],
            SignatureDocumentDataLimits::MAX_SOURCE_RESULT_BYTES,
            'Signature document data result',
        );
    }
}
