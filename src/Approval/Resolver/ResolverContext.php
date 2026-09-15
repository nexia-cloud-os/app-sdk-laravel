<?php

declare(strict_types=1);

namespace Nexia\Approval\Resolver;

use Nexia\Approval\Contracts\ResourceSubjectActor;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

final readonly class ResolverContext
{
    /** @param array<string, mixed> $payload @param array<string, mixed> $config */
    public function __construct(
        public Actor $submitter,
        public LegalEntity $legalEntity,
        public ?ResourceRef $resourceRef = null,
        public array $payload = [],
        public array $config = [],
        /**
         * The person `$resourceRef` is about, when its owning App declared a
         * {@see ResourceSubjectActor}. Core fills this in before a resolver
         * runs; callers leave it null. Null means no provider answered — a
         * subject-anchored resolver must fail closed rather than fall back to
         * the submitter, which would silently approve the wrong line.
         */
        public ?Actor $subjectActor = null,
    ) {}

    /** @param array<string, mixed> $config */
    public function withConfig(array $config): self
    {
        return new self(
            $this->submitter,
            $this->legalEntity,
            $this->resourceRef,
            $this->payload,
            $config,
            $this->subjectActor,
        );
    }

    /** Core-side enrichment; keeps the caller-supplied fields untouched. */
    public function withSubjectActor(?Actor $subjectActor): self
    {
        return new self(
            $this->submitter,
            $this->legalEntity,
            $this->resourceRef,
            $this->payload,
            $this->config,
            $subjectActor,
        );
    }
}
