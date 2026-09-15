<?php

declare(strict_types=1);

namespace Nexia\Permission;

final class PermissionAppDefinition
{
    /** @var list<PermissionDefinition> */
    private array $definitions = [];

    public function __construct(
        private readonly string $appKey,
        private readonly ?string $group = null,
        private readonly AssignmentScope $assignmentScope = AssignmentScope::LegalEntity,
    ) {}

    /** @param list<string> $actions */
    public function resource(string $resource, array $actions): self
    {
        $this->definitions = [
            ...$this->definitions,
            ...PermissionDefinition::many(
                appKey: $this->appKey,
                resource: $resource,
                actions: $actions,
                group: $this->group,
                assignmentScope: $this->assignmentScope,
            ),
        ];

        return $this;
    }

    /**
     * @return list<PermissionDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }
}
