<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Recipe;

/**
 * Where one reference on a record comes from.
 *
 * Four sources, and each earns its place from something a real file needs:
 *
 *   prerequisite         the row printed a department path, the plan settled it
 *                        to one operating unit, the posting needs that unit
 *   prerequisiteThrough  a worker hangs from the *party* behind an account, not
 *                        from the account. The plan settled the account; the
 *                        party is one relation further
 *   row                  the chain inside one row — the employment hangs off the
 *                        worker this row just created
 *   rowThrough           the posting needs the relationship the employment
 *                        created alongside itself, which is not what the action
 *                        returned
 *
 * One axis rather than four arrays on the spec, because they are the same
 * question — what fills this column — asked of different places.
 *
 * `$relation` names a relation on the record pointed at. It is the one place a
 * recipe touches something only the owning side can define, and that is
 * deliberate: an App naming a relation on its own model is describing its own
 * domain, and an App naming one on a host resource is asking the host for a
 * relation the host published. Neither requires a class name.
 */
final readonly class ImportRef
{
    public const SOURCE_PREREQUISITE = 'prerequisite';

    public const SOURCE_ROW = 'row';

    private function __construct(
        public string $source,
        public string $key,
        public ?string $relation = null,
    ) {}

    /** A record the plan settled for this row's printed value. */
    public static function prerequisite(string $specKey): self
    {
        return new self(self::SOURCE_PREREQUISITE, $specKey);
    }

    /** Something that record points at, one relation further. */
    public static function prerequisiteThrough(string $specKey, string $relation): self
    {
        return new self(self::SOURCE_PREREQUISITE, $specKey, $relation);
    }

    /** A record an earlier spec created for this same row. */
    public static function row(string $specKey): self
    {
        return new self(self::SOURCE_ROW, $specKey);
    }

    /** Something that record points at, one relation further. */
    public static function rowThrough(string $specKey, string $relation): self
    {
        return new self(self::SOURCE_ROW, $specKey, $relation);
    }
}
