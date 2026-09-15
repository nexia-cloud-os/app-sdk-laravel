<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Decision;

/**
 * Rows the import cannot complete, offered for the person to accept as they are.
 *
 * The shape an unresolved reference takes. A manager named in no row of the file
 * and matching no existing worker has no proposal anyone could check, so this
 * blocks — but accepting a row is not the same as guessing at it. Leaving the
 * reference empty is the true statement that nobody knows, and the column is
 * nullable precisely because that state exists.
 *
 * Refusing outright left a person two options that are both worse than the
 * problem: drop three real employees, or go and edit a file whose data was
 * never wrong.
 *
 * Accepted per row, never as a flag. A flag would carry consent forward onto
 * rows a later edit introduced, which is the silent drop the whole check exists
 * to prevent — the person agreed about *these* rows.
 */
final readonly class RowAcceptanceDecision extends ImportDecision
{
    public const TYPE = 'row_acceptance';

    /**
     * @param  list<RowAcceptanceEntry>  $entries  the rows in question
     * @param  array<string, string|int>  $labelParams
     */
    public function __construct(
        string $key,
        string $labelKey,
        public array $entries,
        array $labelParams = [],
    ) {
        parent::__construct($key, $labelKey, $labelParams, blocking: true);
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function toArray(): array
    {
        return [
            'type' => self::TYPE,
            'key' => $this->key,
            'label_key' => $this->labelKey,
            'label_params' => $this->labelParams,
            'blocking' => $this->blocking,
            'entries' => array_map(
                static fn (RowAcceptanceEntry $entry): array => $entry->toArray(),
                $this->entries,
            ),
        ];
    }
}
