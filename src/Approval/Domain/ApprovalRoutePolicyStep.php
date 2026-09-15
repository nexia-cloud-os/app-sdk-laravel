<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain;

/**
 * One stored approval-route step: a resolver type and its static configuration.
 */
final readonly class ApprovalRoutePolicyStep
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public string $resolverType,
        public array $config = [],
    ) {}

    /**
     * @return array{resolver_type: string, config: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'resolver_type' => $this->resolverType,
            'config' => $this->config,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            resolverType: (string) ($data['resolver_type'] ?? ''),
            config: is_array($data['config'] ?? null) ? $data['config'] : [],
        );
    }
}
