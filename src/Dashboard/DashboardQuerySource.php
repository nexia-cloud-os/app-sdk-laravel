<?php

declare(strict_types=1);

namespace Nexia\Dashboard;

use InvalidArgumentException;
use Nexia\Agent\AgentToolExecutor;
use Nexia\Agent\AgentToolLane;
use Nexia\Agent\AgentToolRouting;
use Nexia\Agent\AgentToolSurface;
use Nexia\Permission\SubjectPopulation;

/** Read-only App query that may feed a human Dashboard and, when opted in, an agent tool. */
final readonly class DashboardQuerySource
{
    private const TOOL_KEY_PATTERN = '/\A[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_]*)+\z/D';

    /** @param list<class-string> $invalidatedModels */
    public function __construct(
        public string $key,
        public string $description,
        public string $path,
        public string $permission,
        public SubjectPopulation $subjectPopulation,
        public array $invalidatedModels = [],
        public bool $pollingOnly = false,
        public RendererCapability $capability = RendererCapability::HumanOnly,
    ) {
        if ($key !== trim($key) || preg_match(self::TOOL_KEY_PATTERN, $key) !== 1) {
            throw new InvalidArgumentException(
                'Dashboard query source key must contain an App namespace followed by lowercase dotted identifiers.',
            );
        }
        if ($description === '' || $description !== trim($description)) {
            throw new InvalidArgumentException(
                "Dashboard query source {$key} description must be a non-empty trimmed string.",
            );
        }
        if (
            $path === ''
            || $path !== trim($path)
            || ! str_starts_with($path, '/')
            || str_contains($path, '?')
            || str_contains($path, '#')
        ) {
            throw new InvalidArgumentException(
                "Dashboard query source {$key} path must be an absolute route without a query or fragment.",
            );
        }
        if ($permission === '' || $permission !== trim($permission)) {
            throw new InvalidArgumentException("Dashboard query source {$key} must declare one exact capability.");
        }
        if (! array_is_list($invalidatedModels)) {
            throw new InvalidArgumentException(
                "Dashboard query source {$key} model invalidations must be a list.",
            );
        }
        foreach ($invalidatedModels as $modelClass) {
            if (
                ! is_string($modelClass)
                || $modelClass === ''
                || $modelClass !== trim($modelClass)
            ) {
                throw new InvalidArgumentException("Dashboard query source {$key} has an invalid model-invalidation entry.");
            }
        }
        if (count(array_unique($invalidatedModels)) !== count($invalidatedModels)) {
            throw new InvalidArgumentException(
                "Dashboard query source {$key} model invalidations must be unique.",
            );
        }
        if (($invalidatedModels !== []) === $pollingOnly) {
            throw new InvalidArgumentException(
                "Dashboard query source {$key} must declare either model invalidation or polling-only freshness.",
            );
        }
    }

    /** @return array<string, mixed> */
    public function agentManifestRow(string $appKey): array
    {
        return [
            'key' => $this->key,
            'tier' => 'auto',
            'description' => $this->description,
            'executor' => AgentToolExecutor::Laravel->value,
            'routing' => AgentToolRouting::read(
                AgentToolLane::Dashboard,
                AgentToolSurface::Dashboard,
            )->toArray(),
            'http' => [
                'method' => 'POST',
                'path' => '/api/agent/dashboard-query-sources/'.rawurlencode($this->key).'/invoke',
            ],
            'permissions' => [$this->permission],
            'input_schema' => null,
            'app_key' => $appKey,
        ];
    }

    /**
     * Agent policy/execution definition, derived from the exact manifest
     * binding rather than independently restating its method and path.
     *
     * @return array<string, mixed>
     */
    public function agentDefinitionRow(string $appKey): array
    {
        $manifest = $this->agentManifestRow($appKey);
        /** @var array{method: string, path: string} $http */
        $http = $manifest['http'];

        return [
            'key' => $manifest['key'],
            'tier' => $manifest['tier'],
            'executor' => $manifest['executor'],
            'routing' => $manifest['routing'],
            'method' => $http['method'],
            'path' => $http['path'],
            'permission' => $this->permission,
            'subject_population' => $this->subjectPopulation->value,
            'description' => $manifest['description'],
            'app_key' => $manifest['app_key'],
            'agent_visible' => $this->capability->allowsAgent(),
            'dashboard_query_source' => true,
        ];
    }

    /**
     * Human Dashboard backing source. This is deliberately not an Agent tool
     * definition: the Agent invokes the POST dispatcher declared above, while
     * the Dashboard bridge reads this GET source after its own authorization
     * and Subject Population checks.
     *
     * @return array<string, mixed>
     */
    public function backingSourceRow(string $appKey): array
    {
        return [
            'key' => $this->key,
            'routing' => AgentToolRouting::read(
                AgentToolLane::Dashboard,
                AgentToolSurface::Dashboard,
            )->toArray(),
            'method' => 'GET',
            'path' => $this->path,
            'permission' => $this->permission,
            'subject_population' => $this->subjectPopulation->value,
            'description' => $this->description,
            'app_key' => $appKey,
            'agent_visible' => $this->capability->allowsAgent(),
            'dashboard_query_source' => true,
        ];
    }
}
