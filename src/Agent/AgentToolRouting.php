<?php

declare(strict_types=1);

namespace Nexia\Agent;

/**
 * App-neutral routing metadata consumed by the host Tool Router.
 *
 * The contract deliberately carries no Laravel action, endpoint, prompt, or
 * model concern. Apps describe where a tool belongs; Core and the Gateway own
 * selection, authorization, and execution.
 */
final readonly class AgentToolRouting
{
    /**
     * @param  non-empty-list<AgentToolLane>  $lanes
     * @param  list<string>  $preconditions
     */
    public function __construct(
        public array $lanes,
        public AgentToolEffect $effect,
        public AgentToolSurface $surface = AgentToolSurface::None,
        public array $preconditions = [],
    ) {
        if (! array_is_list($lanes) || $lanes === []) {
            throw new \InvalidArgumentException('Agent tool routing lanes must be a non-empty list.');
        }

        foreach ($lanes as $lane) {
            if (! $lane instanceof AgentToolLane) {
                throw new \InvalidArgumentException('Agent tool routing lanes must be AgentToolLane values.');
            }
        }

        if (count(array_unique(array_map(
            static fn (AgentToolLane $lane): string => $lane->value,
            $lanes,
        ))) !== count($lanes)) {
            throw new \InvalidArgumentException('Agent tool routing lanes must be unique.');
        }

        if (! array_is_list($preconditions)) {
            throw new \InvalidArgumentException('Agent tool preconditions must be a list.');
        }

        foreach ($preconditions as $precondition) {
            if (
                ! is_string($precondition)
                || trim($precondition) === ''
                || $precondition !== trim($precondition)
            ) {
                throw new \InvalidArgumentException('Agent tool preconditions must be non-empty strings.');
            }
        }

        if (count(array_unique($preconditions)) !== count($preconditions)) {
            throw new \InvalidArgumentException('Agent tool preconditions must be unique.');
        }
    }

    /** @param list<string> $preconditions */
    public static function read(
        AgentToolLane $lane,
        AgentToolSurface $surface = AgentToolSurface::None,
        array $preconditions = [],
    ): self {
        return new self([$lane], AgentToolEffect::Read, $surface, $preconditions);
    }

    /** @param list<string> $preconditions */
    public static function draft(
        AgentToolLane $lane,
        AgentToolSurface $surface,
        array $preconditions = [],
    ): self {
        return new self([$lane], AgentToolEffect::Draft, $surface, $preconditions);
    }

    /** @param list<string> $preconditions */
    public static function mutate(
        AgentToolLane $lane,
        AgentToolSurface $surface,
        array $preconditions = [],
    ): self {
        return new self([$lane], AgentToolEffect::Mutate, $surface, $preconditions);
    }

    /** @param list<string> $preconditions */
    public static function external(
        AgentToolLane $lane,
        AgentToolSurface $surface,
        array $preconditions = [],
    ): self {
        return new self([$lane], AgentToolEffect::External, $surface, $preconditions);
    }

    /** @return array{lanes: non-empty-list<string>, effect: string, surface: string, preconditions: list<string>} */
    public function toArray(): array
    {
        return [
            'lanes' => array_values(array_map(
                static fn (AgentToolLane $lane): string => $lane->value,
                $this->lanes,
            )),
            'effect' => $this->effect->value,
            'surface' => $this->surface->value,
            'preconditions' => array_values($this->preconditions),
        ];
    }
}
