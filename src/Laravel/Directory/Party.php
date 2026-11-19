<?php

declare(strict_types=1);

namespace Nexia\Laravel\Directory;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Nexia\Identity\Contracts\Actor;
use Nexia\Identity\Contracts\BatchCanonicalPartyDirectory;
use Nexia\Identity\Contracts\BatchPartyDirectory;
use Nexia\Identity\Contracts\Party as PartyIdentity;
use Nexia\Identity\Contracts\PartyBusinessDirectory;
use Nexia\Identity\Contracts\PartyRelationshipDirectory;
use Nexia\Identity\PartyRelationship;
use Nexia\Identity\PersonDirectoryEntry;
use Nexia\Laravel\Identity\Contracts\PartyDirectory;

final class Party extends DirectoryModel
{
    use SoftDeletes;

    protected $table = 'public.nexia_directory_parties';

    protected function casts(): array
    {
        return ['person' => 'boolean', 'organization' => 'boolean', 'archived' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), 'active');
    }

    public function scopeMatchingName(Builder $query, string $search): Builder
    {
        $term = '%'.addcslashes($search, '\\%_').'%';

        return $query->where(fn (Builder $names) => $names
            ->where($this->qualifyColumn('display_name'), 'ilike', $term)
            ->orWhere($this->qualifyColumn('legal_name'), 'ilike', $term));
    }

    public function scopeWithRoles(Builder $query, Actor $actor, array $roles): Builder
    {
        $directory = app(PartyBusinessDirectory::class);
        $ids = [];
        for ($page = 1; ; $page++) {
            // shortcut: bound the lookup before the App SQL runs; use searchByRoles for a broad directory.
            if ($page > 100) {
                throw new \LengthException('Party role scope exceeds 10,000 matches.');
            }
            $result = $directory->search($actor, '', $roles, [], $page, 100);
            foreach ($result['items'] as $party) {
                $ids[$party['public_id']] = true;
            }
            if (! $result['has_more']) {
                return $query->whereIn($this->qualifyColumn('public_id'), array_keys($ids));
            }
        }
    }

    public static function searchByRoles(Actor $actor, array $roles, string $search = '', array $types = [], int $perPage = 25, int $page = 1): Paginator
    {
        $result = app(PartyBusinessDirectory::class)->search($actor, $search, $roles, $types, $page, $perPage);

        return (new Paginator($result['items'], $perPage, $page))->hasMorePagesWhen($result['has_more']);
    }

    public static function canonical(string $publicId): ?self
    {
        $party = app(PartyDirectory::class)->findCanonicalByPublicId($publicId);

        return $party === null ? null : self::fromIdentity($party);
    }

    public static function canonicalMany(array $publicIds): Collection
    {
        $directory = app(PartyDirectory::class);
        $parties = $directory instanceof BatchCanonicalPartyDirectory
            ? $directory->findCanonicalByPublicIds($publicIds)
            : array_filter(array_map($directory->findCanonicalByPublicId(...), array_unique($publicIds)));

        return collect(array_values($parties))->map(self::fromIdentity(...));
    }

    public static function peopleByEmail(string $email, int $limit = 2): Collection
    {
        return collect(app(PartyDirectory::class)->activePeopleByEmail($email, $limit))->map(self::fromIdentity(...));
    }

    public static function peopleByEmails(array $emails, int $limit = 2): Collection
    {
        $directory = app(PartyDirectory::class);
        if (! $directory instanceof BatchPartyDirectory) {
            throw new \LogicException('The host does not support batch Party email lookup.');
        }

        return collect($directory->activePeopleByEmails($emails, $limit))
            ->map(static fn (array $people) => collect($people)->map(self::fromIdentity(...)));
    }

    public static function searchPeople(string $search, int $limit = 25, array $excludedKeys = []): Collection
    {
        return collect(app(PartyDirectory::class)->searchActivePeople($search, $limit, $excludedKeys))
            ->map(static fn (PersonDirectoryEntry $person) => (new self)->newFromBuilder([
                'key' => $person->key, 'public_id' => $person->publicId, 'display_label' => $person->displayLabel,
                'person' => true, 'organization' => false, 'archived' => false, 'contact_email' => $person->contactEmail,
            ]));
    }

    public static function roles(Actor $actor, array $publicIds): Collection
    {
        return collect(app(PartyBusinessDirectory::class)->activeRoles($actor, $publicIds));
    }

    public static function relationships(Actor $actor, array $publicIds): Collection
    {
        return collect(app(PartyBusinessDirectory::class)->activeRelationships($actor, $publicIds));
    }

    public static function relationship(Actor $actor, string $publicId): ?PartyRelationship
    {
        return app(PartyRelationshipDirectory::class)->findVisibleByPublicId($actor, $publicId);
    }

    private static function fromIdentity(PartyIdentity $party): self
    {
        return (new self)->newFromBuilder([
            'key' => $party->key(), 'public_id' => $party->publicId(), 'display_label' => $party->displayLabel(),
            'person' => $party->isPerson(), 'organization' => $party->isOrganization(), 'archived' => $party->isArchived(),
        ]);
    }
}
