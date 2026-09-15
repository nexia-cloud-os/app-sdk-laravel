<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Recipe;

/**
 * One file column, and the resource the values in it name.
 *
 * A record decided once per distinct value. Forty rows saying `재무팀` mean one
 * department; three hundred rows name perhaps nine jobs. Deciding per row would
 * let the same name be matched on one row and created on another, which is how
 * a file ends up with the department it names twice.
 *
 * This is the mapping half of the intersection — the part no schema can carry,
 * because it depends on the file rather than on the resource. The recipe states
 * it; the host derives the creation order from the resources named here and the
 * relations between them.
 *
 * `$resourceKey`, never a class name. A recipe that had to name
 * `App\Models\Tenant\OperatingUnit` would be a recipe only the host could write,
 * and that is precisely how personnel registration ended up owned by the wrong
 * side of the boundary.
 */
final readonly class ImportPrerequisite
{
    /**
     * @param  string  $resourceKey  the resource these values name
     * @param  string|null  $column  the file column carrying the printed value
     * @param  list<string>  $path  file columns forming a hierarchy, shallowest
     *         first, for the one shape a flat column cannot express. A personnel
     *         export writes an operating unit as division, department and team,
     *         and forty rows repeat the same division — the values are not
     *         independent, each names a node whose parent is the column to its
     *         left. Mutually exclusive with `$column`
     * @param  string  $attribute  the attribute key the printed value fills
     * @param  array<string, mixed>  $constants  attributes every created record gets
     * @param  array<string, string>  $attributesFrom  attribute key → file column,
     *         for a record needing more than the value that identifies it. An
     *         account is identified by its email and still needs a name
     * @param  array<string, string>  $derivedFrom  attribute key → the attribute it
     *         is derived from, for a required column no file carries. Declared
     *         rather than defaulted: a resource whose code means something must
     *         not have one invented for it, and the owning side is the only one
     *         that knows which those are
     * @param  string|null  $linkedTo  another spec whose record this one has to be
     *         linked to. An operating unit is invisible to the organization
     *         directory until it is affiliated with a legal entity, so a unit
     *         this import created would be handed back as a public id resolving
     *         to nothing.
     *
     *         Only the *other end* is named. How the link is made belongs to the
     *         resource that owns it and is published there, because an action
     *         class name is exactly the kind of thing a recipe must not carry
     * @param  array<string, string>  $perDecision  attribute key → the decision key
     *         whose settled value fills it. For what the file cannot carry and a
     *         person answered on the approval card: a grade's level is NOT NULL,
     *         no HR export has it, and the ladder comes from the card. The recipe
     *         names which decision fills which attribute; it does not carry the
     *         values, which arrive with the approval
     */
    public function __construct(
        public string $resourceKey,
        public ?string $column = null,
        public array $path = [],
        public string $attribute = 'name',
        public array $constants = [],
        public array $attributesFrom = [],
        public array $derivedFrom = [],
        public ?string $linkedTo = null,
        public array $perDecision = [],
    ) {}

    public function isPath(): bool
    {
        return $this->path !== [];
    }

    /** @return list<string> the file columns this spec reads */
    public function columns(): array
    {
        return $this->isPath() ? $this->path : array_values(array_filter([$this->column]));
    }
}
