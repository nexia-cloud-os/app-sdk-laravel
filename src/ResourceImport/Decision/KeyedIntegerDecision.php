<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Decision;

/**
 * A whole number per named thing, pre-filled and editable.
 *
 * The shape a grade ladder takes: the file names 사원, 과장 and 부장, each needs
 * a rank, no export carries one, and asking with empty boxes does not work —
 * the same principle the fill-rule form already rests on. So a value arrives
 * pre-filled and the approval is not held for it.
 *
 * `basisKey` is what makes pre-filling safe. A proposal a user cannot check is
 * a guess wearing a suit, so each entry says where its number came from. The
 * personnel ladder distinguishes a value derived from vocabulary — the grade
 * names matched a known ladder, so 부장 outranks 과장 whatever order the file
 * listed them in — from one derived from where each name first appeared, which
 * is an accident of how the vendor sorted their export and only a person can
 * confirm.
 *
 * Bounded because the values are ordinal and land in integer columns. A rank of
 * zero or a negative one orders a ladder nobody described, and an unbounded one
 * invites a value that overflows the column.
 */
final readonly class KeyedIntegerDecision extends ImportDecision
{
    public const TYPE = 'keyed_integer';

    /**
     * @param  list<KeyedIntegerEntry>  $entries  one per named thing, in the order
     *         the card should draw them
     * @param  int  $min  lowest value a reply may carry
     * @param  int  $max  highest value a reply may carry
     * @param  array<string, string|int>  $labelParams
     */
    public function __construct(
        string $key,
        string $labelKey,
        public array $entries,
        public int $min = 1,
        public int $max = 999,
        array $labelParams = [],
    ) {
        parent::__construct($key, $labelKey, $labelParams, blocking: false);
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
            'min' => $this->min,
            'max' => $this->max,
            'entries' => array_map(
                static fn (KeyedIntegerEntry $entry): array => $entry->toArray(),
                $this->entries,
            ),
        ];
    }
}
