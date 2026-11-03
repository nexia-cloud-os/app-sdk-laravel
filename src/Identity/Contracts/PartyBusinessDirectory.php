<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Actor-authorized business classifications and directed relationships. */
interface PartyBusinessDirectory
{
    /**
     * Active role assignments for visible Parties, keyed by requested public ID.
     * Missing or inaccessible Parties are omitted. Each role includes its code,
     * display name, and effective dates. At most 100 IDs per call.
     *
     * @param list<string> $partyPublicIds
     * @return array<string, list<array{code:string, display_name:string, active:bool, granted_at:?string, ended_at:?string}>>
     */
    public function activeRoles(Actor $actor, array $partyPublicIds): array;

    /**
     * Search active visible Parties with all requested roles; an empty type list
     * selects both persons and organizations. Results are ordered before paging.
     *
     * @param list<string> $roleCodes
     * @param list<'person'|'organization'> $types
     * @return array{items:list<array{public_id:string, display_label:string, type:string}>, has_more:bool}
     */
    public function search(Actor $actor, string $query, array $roleCodes, array $types, int $page, int $perPage): array;

    /**
     * Active, visible directed relationships for each visible Party. The other
     * endpoint is returned only when it is also visible to the actor.
     *
     * @param list<string> $partyPublicIds
     * @return array<string, list<array{public_id:string, code:string, display_name:?string, active:bool, direction:string, other_party_public_id:string, other_party_label:string, started_at:?string, ended_at:?string}>>
     */
    public function activeRelationships(Actor $actor, array $partyPublicIds): array;
}
