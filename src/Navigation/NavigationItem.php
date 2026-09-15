<?php

declare(strict_types=1);

namespace Nexia\Navigation;

use InvalidArgumentException;
use Nexia\Permission\SubjectPopulation;
use ArrayAccess;
use IteratorAggregate;
use LogicException;
use Traversable;

final readonly class NavigationItem implements ArrayAccess, IteratorAggregate
{
    /**
     * @param string|list<string>|null $permission
     * @param list<array<string, mixed>>|null $agentActions
     * @param list<string>|null $activePatterns
     * @param list<string>|null $subjectPopulations
     */
    public function __construct(
        public string $id,
        public string $route,
        public string|array|null $permission,
        public string $icon,
        public int $sort,
        public string $labelKey,
        public string $appKey,
        public string $contextId,
        public ?string $groupId,
        public ?string $subgroupId,
        public bool $searchable,
        public ?string $descriptionKey = null,
        public ?array $agentActions = null,
        public ?int $subgroupOrder = null,
        public ?string $parentId = null,
        public ?array $activePatterns = null,
        public ?bool $activityRail = null,
        public ?array $subjectPopulations = null,
    ) {}

    /**
     * @param  string|list<string>|null  $permission
     * @param  list<array<string, mixed>>|null  $agentActions
     * @param  list<string>|null  $activePatterns
     * @param  list<SubjectPopulation|string>|null  $subjectPopulations
     * @return array{id: string, route: string, permission: string|list<string>|null, subject_populations?: list<string>, icon: string, sort: int, label_key: string, description_key?: string, app_key: string, context_id: string, group_id: string|null, subgroup_id: string|null, searchable: bool, subgroup_order?: int, agent_actions?: list<array<string, mixed>>, parent_id?: string, active_patterns?: list<string>, activity_rail?: bool}
     */
    public static function make(
        string $id,
        string $route,
        string $icon,
        int $sort,
        string $labelKey,
        string $appKey,
        string $contextId,
        ?string $groupId,
        ?string $subgroupId = null,
        string|array|null $permission = null,
        ?string $descriptionKey = null,
        ?array $agentActions = null,
        ?int $subgroupOrder = null,
        ?string $parentId = null,
        ?array $activePatterns = null,
        ?bool $activityRail = null,
        ?array $subjectPopulations = null,
        bool $searchable = true,
    ): self {
        $resolvedContextId = trim($contextId) === 'app' ? 'app:'.$appKey : trim($contextId);
        $resolvedGroupId = self::normalizeOptionalId($groupId);
        $resolvedSubgroupId = self::normalizeOptionalId($subgroupId);

        if ($resolvedContextId === '') {
            throw new InvalidArgumentException('Navigation context must be non-empty.');
        }
        if ($resolvedSubgroupId !== null && $resolvedGroupId === null) {
            throw new InvalidArgumentException('Navigation subgroup requires a group.');
        }

        $populationValues = null;
        if ($subjectPopulations !== null) {
            $values = array_values(array_unique(array_map(
                static function (SubjectPopulation|string $population): string {
                    $value = $population instanceof SubjectPopulation ? $population->value : $population;
                    if (SubjectPopulation::tryFrom($value) === null) {
                        throw new InvalidArgumentException("Invalid navigation subject population [{$value}].");
                    }

                    return $value;
                },
                $subjectPopulations,
            )));
            $populationValues = $values === [] ? null : $values;
        }
        $normalizedParentId = $parentId === null || trim($parentId) === '' ? null : trim($parentId);
        $patterns = null;
        if ($activePatterns !== null) {
            $patterns = array_values(array_filter(
                array_map(static fn (mixed $pattern): string => is_string($pattern) ? trim($pattern) : '', $activePatterns),
                static fn (string $pattern): bool => $pattern !== '',
            ));
            $patterns = $patterns === [] ? null : $patterns;
        }

        return new self(
            id: $id,
            route: $route,
            permission: $permission,
            icon: $icon,
            sort: $sort,
            labelKey: $labelKey,
            appKey: $appKey,
            contextId: $resolvedContextId,
            groupId: $resolvedGroupId,
            subgroupId: $resolvedSubgroupId,
            searchable: $searchable,
            descriptionKey: $descriptionKey,
            agentActions: $agentActions,
            subgroupOrder: $subgroupOrder,
            parentId: $normalizedParentId,
            activePatterns: $patterns,
            activityRail: $activityRail,
            subjectPopulations: $populationValues,
        );
    }

    private static function normalizeOptionalId(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $entry = [
            'id' => $this->id,
            'route' => $this->route,
            'permission' => $this->permission,
            'icon' => $this->icon,
            'sort' => $this->sort,
            'label_key' => $this->labelKey,
            'app_key' => $this->appKey,
            'context_id' => $this->contextId,
            'group_id' => $this->groupId,
            'subgroup_id' => $this->subgroupId,
            'searchable' => $this->searchable,
        ];
        if ($this->subjectPopulations !== null) {
            $entry['subject_populations'] = $this->subjectPopulations;
        }
        if ($this->descriptionKey !== null) {
            $entry['description_key'] = $this->descriptionKey;
        }
        if ($this->agentActions !== null) {
            $entry['agent_actions'] = $this->agentActions;
        }
        if ($this->subgroupOrder !== null) {
            $entry['subgroup_order'] = $this->subgroupOrder;
        }
        if ($this->parentId !== null) {
            $entry['parent_id'] = $this->parentId;
        }
        if ($this->activePatterns !== null) {
            $entry['active_patterns'] = $this->activePatterns;
        }
        if ($this->activityRail !== null) {
            $entry['activity_rail'] = $this->activityRail;
        }

        return $entry;
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? ($this->toArray()[$offset] ?? null) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Navigation items are immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Navigation items are immutable.');
    }

    public function getIterator(): Traversable
    {
        yield from $this->toArray();
    }
}
