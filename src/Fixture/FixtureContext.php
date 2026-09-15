<?php

declare(strict_types=1);

namespace Nexia\Fixture;

use InvalidArgumentException;
use LogicException;

/** Mutable reference exchange around immutable host-owned fixture inputs. */
final class FixtureContext
{
    /** @var array<string, FixtureLegalEntity> */
    private array $legalEntities = [];

    /** @var array<string, FixtureSubject> */
    private array $subjects = [];

    /** @var array<string, FixtureReference> */
    private array $references = [];

    /**
     * @param  list<FixtureLegalEntity>  $legalEntities
     * @param  list<FixtureSubject>  $subjects
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $fixtureKey,
        array $legalEntities,
        array $subjects,
        public readonly int $actorUserId,
        public readonly string $actorPublicId,
        private readonly array $payload = [],
    ) {
        if (trim($this->fixtureKey) === '' || $this->actorUserId < 1 || trim($this->actorPublicId) === '') {
            throw new InvalidArgumentException('Fixture context identity must be complete.');
        }

        foreach ($legalEntities as $legalEntity) {
            if (isset($this->legalEntities[$legalEntity->code])) {
                throw new LogicException("Fixture Legal Entity [{$legalEntity->code}] is duplicated.");
            }

            $this->legalEntities[$legalEntity->code] = $legalEntity;
        }

        foreach ($subjects as $subject) {
            if (isset($this->subjects[$subject->key])) {
                throw new LogicException("Fixture subject [{$subject->key}] is duplicated.");
            }

            $this->subjects[$subject->key] = $subject;
        }
    }

    /** @return list<FixtureLegalEntity> */
    public function legalEntities(): array
    {
        return array_values($this->legalEntities);
    }

    public function legalEntity(string $code): ?FixtureLegalEntity
    {
        return $this->legalEntities[$code] ?? null;
    }

    /** @return list<FixtureSubject> */
    public function subjects(): array
    {
        return array_values($this->subjects);
    }

    public function subject(string $key): ?FixtureSubject
    {
        return $this->subjects[$key] ?? null;
    }

    public function payload(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }

    public function publish(FixtureReference $reference): void
    {
        $key = $this->referenceKey($reference->resourceKey, $reference->subjectKey);

        if (isset($this->references[$key]) && $this->references[$key] != $reference) {
            throw new LogicException(
                "Fixture reference [{$reference->resourceKey}:{$reference->subjectKey}] was published twice with different identities.",
            );
        }

        $this->references[$key] = $reference;
    }

    public function reference(string $resourceKey, string $subjectKey): ?FixtureReference
    {
        return $this->references[$this->referenceKey($resourceKey, $subjectKey)] ?? null;
    }

    /** @return list<FixtureReference> */
    public function references(): array
    {
        return array_values($this->references);
    }

    private function referenceKey(string $resourceKey, string $subjectKey): string
    {
        return $resourceKey."\0".$subjectKey;
    }
}
