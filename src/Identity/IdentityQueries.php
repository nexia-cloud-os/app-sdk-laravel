<?php

declare(strict_types=1);

namespace Nexia\Identity;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Nexia\Identity\Contracts\ActorDirectory;
use Nexia\Identity\Contracts\Actor;
use Nexia\Identity\Contracts\PartyBusinessDirectory;
use Nexia\Identity\Contracts\PartyRelationshipDirectory;
use Nexia\Identity\Contracts\PersonProfileDirectory;
use Nexia\Identity\Query\IdentityQuery;
use Nexia\Identity\Query\IdentityQueryBatch;
use Nexia\Identity\Query\IdentityBusinessQuery;
use Nexia\Identity\Query\IdentityRead;
use Nexia\Laravel\Identity\Contracts\PartyDirectory;

/** Standard identity reads without changing the existing host contracts. */
final readonly class IdentityQueries
{
    public function __construct(
        private Container $container,
    ) {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
    }

    public function query(string $target, ?Actor $actor = null): IdentityQuery|IdentityBusinessQuery
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        if ($actor !== null && $target !== 'party_business') {
            return new IdentityQuery('unsupported', null);
        }
        return match ($target) {
            'party', 'user', 'profile' => new IdentityQuery($target, $this),
            'party_business' => new IdentityBusinessQuery($actor, $this, $this),
            default => new IdentityQuery($target, null),
        };
    }

    /** @param list<IdentityRead> $queries */
    public function queryBatch(array $queries): IdentityQueryBatch
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        return new IdentityQueryBatch($queries);
    }

    /** @internal Resolve only the contract needed by the selected operation. */
    public function resolveDirectory(string $target): object|null
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $contract = $this->contractFor($target);
        if ($contract === null || ! $this->container->bound($contract)) {
            return null;
        }
        $directory = $this->container->make($contract);
        if (! $directory instanceof $contract) {
            throw new \RuntimeException('Invalid identity directory binding.');
        }

        return $directory;
    }

    /** @internal A transient binding may restore a different authority for each read. */
    public function sharesDirectory(string $target): bool
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $contract = $this->contractFor($target);
        return $contract !== null && $this->container instanceof \Illuminate\Container\Container
            && $this->container->isShared($contract);
    }

    private function contractFor(string $target): ?string
    {
        return match ($target) {
            'party' => PartyDirectory::class,
            'user' => ActorDirectory::class,
            'profile' => PersonProfileDirectory::class,
            'business' => PartyBusinessDirectory::class,
            'relationship' => PartyRelationshipDirectory::class,
            default => null,
        };
    }
}
