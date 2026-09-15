<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;
use Laravel\Scout\Builder as ScoutBuilder;
use Laravel\Scout\Engines\DatabaseEngine;
use Laravel\Scout\Engines\Engine;
use Laravel\Scout\Searchable;
use LogicException;
use Nexia\Laravel\Models\ScoutSearchResolverRegistry;
use Nexia\Laravel\Resources\ResourceListFields;
use ReflectionMethod;

/**
 * Scout integration for models whose list `?search=` runs through the
 * Scout engine seam (`Model::search()`), derived from the `Filterable`
 * list search catalog.
 *
 * The Scout index projection is derived from `resourceListFields()`
 * so the search index and the list search contract share one source of
 * truth. Under the `database` engine, Scout turns each
 * `toSearchableArray()` key into a SQL `LIKE`/`ILIKE` predicate, so the
 * projection must contain only real table columns — deriving it from
 * the searchable field declarations keep relation-derived or non-column keys
 * out of the generated `WHERE` clause. A richer index projection for a
 * real engine (e.g. Meilisearch) is an explicit per-model override.
 *
 * @see Filterable::resourceListFields()
 */
trait UsesFilterableScoutSearch
{
    use Searchable;

    /**
     * Derive the Scout index projection from the Filterable list search
     * catalog. Returns an empty projection when the model declares no
     * searchable columns, which makes `?search=` a no-op rather than an
     * unbounded full-table scan.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $projection = [];
        $usesExternalEngine = $this->usesExternalScoutEngine();
        $columns = $usesExternalEngine
            ? $this->externalSearchableColumns()
            : $this->resourceListSearchableColumns();
        foreach ($columns as $column) {
            $value = $usesExternalEngine
                ? $this->externalSearchableValue($column)
                : $this->getAttribute($column);

            // A null searchable value is an absent document field, not an empty
            // string. Typesense rejects null for a declared string field, so the
            // projection omits it and the collection schema marks it optional.
            if ($value === null && $usesExternalEngine) {
                continue;
            }

            $projection[$column] = $value;
            if ($usesExternalEngine && $this->isTitleLikeSearchColumn($column)) {
                $projection[$column.'_compact'] = preg_replace('/\s+/u', '', (string) $value) ?? '';
                $projection[$column.'_jamo'] = static::decomposeHangulJamo((string) $value);
            }
        }

        // The database engine deliberately derives its predicates from real
        // table columns. Metadata for an external shared index must therefore
        // stay out of that projection, otherwise existing list `?search=` SQL
        // would try to query virtual columns.
        if ($usesExternalEngine) {
            $identity = $this->searchDocumentIdentity();
            $projection['id'] = $identity['id'];
            $projection['tenant_key'] = $identity['tenant_key'];
            $projection['resource_key'] = $identity['resource_key'];
            $projection['public_id'] = $identity['public_id'];
        }

        return $projection;
    }

    /**
     * Apply the Scout database engine's ordinary substring-search predicate
     * directly to the caller's already-scoped Eloquent query.
     *
     * Full-text and prefix attributes deliberately stay on Scout's native
     * path because they have engine-specific query and relevance semantics.
     * External engines also stay on the candidate-key path until their
     * compound identity, tenant filter, and complete-result paging contract
     * can be represented without changing list results.
     *
     * @internal Used by {@see Filterable::applyFilterableSearch()}.
     *
     * @param  list<string>  $columns
     */
    public function applyFilterableDatabaseSearch(Builder $query, string $term, array $columns): bool
    {
        if (! ($this->searchableUsing() instanceof DatabaseEngine)) {
            return false;
        }

        $projection = new ReflectionMethod($this, 'toSearchableArray');
        if ($projection->getAttributes(SearchUsingFullText::class) !== []
            || $projection->getAttributes(SearchUsingPrefix::class) !== []) {
            return false;
        }

        $model = $query->getModel();
        $connectionType = $model->getConnection()->getDriverName();
        $keyName = $model->getScoutKeyName();
        $canSearchPrimaryKey = ctype_digit($term)
            && in_array($model->getKeyType(), ['int', 'integer'], true)
            && ($connectionType !== 'pgsql' || $term <= PHP_INT_MAX)
            && in_array($keyName, $columns, true);
        $operator = $connectionType === 'pgsql' ? 'ilike' : 'like';

        $query->where(function (Builder $search) use ($model, $term, $columns, $keyName, $canSearchPrimaryKey, $operator): void {
            if ($canSearchPrimaryKey) {
                $search->orWhere($model->getQualifiedKeyName(), $term);
            }

            foreach ($columns as $column) {
                if ($canSearchPrimaryKey && $column === $keyName) {
                    continue;
                }

                $search->orWhere($model->qualifyColumn($column), $operator, '%'.$term.'%');
            }
        });

        return true;
    }

    /**
     * The object identity in a shared external search collection. The SDK owns
     * the format; Core supplies only the app-neutral resource-key mapping.
     *
     * @return array{id: string, tenant_key: string, resource_key: string, public_id: string}
     */
    public function searchDocumentIdentity(): array
    {
        $identity = $this->trySearchDocumentIdentity();

        if ($identity === null) {
            throw new LogicException(sprintf(
                'Cannot build an external search identity for [%s] without a tenant, resource key, and public id.',
                static::class,
            ));
        }

        return $identity;
    }

    /**
     * Identity resolution without the exception, for callers that must skip
     * rather than fail — a model saved outside tenant context (seeding,
     * central jobs) or without a catalog resource key must not crash the
     * save; it simply is not exportable to a shared external index.
     *
     * @return array{id: string, tenant_key: string, resource_key: string, public_id: string}|null
     */
    public function trySearchDocumentIdentity(): ?array
    {
        $resolver = ScoutSearchResolverRegistry::identityResolver();
        $tenantKey = $resolver?->tenantKey();
        $resourceKey = $resolver?->resourceKeyForModel(static::class);
        $publicId = $this->getAttribute('public_id') ?? $this->getKey();

        if ($tenantKey === null || $resourceKey === null || $publicId === null || $publicId === '') {
            return null;
        }

        return [
            'id' => (string) $tenantKey.':'.$resourceKey.':'.(string) $publicId,
            'tenant_key' => (string) $tenantKey,
            'resource_key' => $resourceKey,
            'public_id' => (string) $publicId,
        ];
    }

    /**
     * External engines use the compound document identity. Laravel Scout's
     * database engine gets primary keys from Eloquent directly, so preserving
     * its native key keeps the existing list-search lane unchanged.
     */
    public function getScoutKey(): mixed
    {
        if ($this->usesExternalScoutEngine()) {
            // Scout invokes removal after a committed model write as well as
            // for catalog-backed resource records. Models such as tenant
            // notifications have searchable list columns but no public
            // resource identity, so they were never external documents.
            // Keep their native key on the no-document lane instead of
            // throwing while a transaction commits.
            $identity = $this->trySearchDocumentIdentity();
            if ($identity !== null) {
                return $identity['id'];
            }
        }

        return $this->getKey();
    }

    /**
     * Route Scout per model rather than per deployment.
     *
     * The external engine holds one shared collection per deliberately
     * allowlisted resource; every other model keeps the `database` engine's
     * `ILIKE` list-search lane it had before an external driver was
     * configured. Without this seam, `Filterable::applyFilterableSearch()`
     * would send a non-allowlisted model's `?search=` to a collection that
     * was never created — the engine answers with zero keys and the list
     * collapses to `WHERE 1 = 0`.
     *
     * Scout reads the engine exclusively through this method — query
     * (`Builder::engine()`), index (`syncMakeSearchable`, `MakeSearchable`
     * job), removal (`syncRemoveFromSearch`, `RemoveFromSearch` job), and
     * flush (`removeAllFromSearch`) — so one override covers every path.
     * It must stay cheap and network-free: config reads plus the configured
     * SDK resolver registry, and no call back into Scout.
     */
    public function searchableUsing(): Engine
    {
        $resolver = ScoutSearchResolverRegistry::engineResolver();

        return $this->usesExternalScoutEngine() && $this->hasSearchTenantContext()
            ? $resolver->defaultEngine()
            : $resolver->databaseEngine();
    }

    /**
     * Whether this model CLASS belongs to the external lane: an external
     * global driver, a resolvable catalog resource key, and an explicit
     * allowlist entry for it.
     *
     * Deliberately independent of tenant context, so the document shape, the
     * Scout key, and the collection name a removal targets are the same
     * inside and outside a tenant — Scout resolves those on paths that may
     * run centrally.
     */
    private function usesExternalScoutEngine(): bool
    {
        if (config('scout.driver', 'database') === 'database') {
            return false;
        }

        $resourceKey = ScoutSearchResolverRegistry::identityResolver()?->resourceKeyForModel(static::class);
        if ($resourceKey === null) {
            return false;
        }

        $allowlist = config('search.typesense.resource_allowlist', []);

        return is_array($allowlist) && in_array($resourceKey, $allowlist, true);
    }

    /**
     * Tenant context is what makes an external query or write meaningful: the
     * shared collection is partitioned by `tenant_key`, and a document
     * identity cannot be built without one. Outside tenancy the model falls
     * back to the database lane, whose write operations are no-ops, instead
     * of addressing the shared collection unscoped.
     */
    private function hasSearchTenantContext(): bool
    {
        return ScoutSearchResolverRegistry::identityResolver()?->tenantKey() !== null;
    }

    private function isTitleLikeSearchColumn(string $column): bool
    {
        return preg_match('/(?:^|_)(?:title|name|subject|label)(?:$|_)/i', $column) === 1;
    }

    private function isIdentifierSearchColumn(string $column): bool
    {
        return preg_match('/(?:code|email|uuid|public_id)$/i', $column) === 1;
    }

    /**
     * Index only models that expose actual list-search columns. This is a
     * no-op in the Scout database driver, but prevents empty search documents
     * from being sent to real engines such as Meilisearch.
     */
    public function shouldBeSearchable(): bool
    {
        if ($this->resourceListSearchableColumns() === []) {
            return false;
        }

        // Database-lane models stay searchable exactly as they are under the
        // `database` driver. The engine stores nothing, so this only keeps the
        // list-search contract uniform across deployments.
        if (! $this->usesExternalScoutEngine()) {
            return true;
        }

        // Fail closed without crashing the save: a model outside tenant
        // context or without a public id has no shared-collection identity,
        // so it is simply not exported.
        return $this->trySearchDocumentIdentity() !== null;
    }

    public function searchableAs(): string
    {
        if ($this->usesExternalScoutEngine()) {
            // Collection naming must not depend on tenant context (delete
            // and query paths may run outside it); the resource key alone
            // names the shared collection.
            $resourceKey = ScoutSearchResolverRegistry::identityResolver()?->resourceKeyForModel(static::class);
            if ($resourceKey !== null) {
                return $resourceKey;
            }
        }

        return config('scout.prefix').$this->getTable();
    }

    /** @return array{name: string, fields: list<array<string, mixed>>} */
    public function typesenseCollectionSchema(): array
    {
        $fields = [
            ['name' => 'id', 'type' => 'string'],
            ['name' => 'tenant_key', 'type' => 'string', 'facet' => true],
            ['name' => 'resource_key', 'type' => 'string', 'facet' => true],
            ['name' => 'public_id', 'type' => 'string'],
        ];
        $reserved = array_column($fields, 'name');
        foreach ($this->externalSearchableColumns() as $column) {
            // Models commonly list identity columns (public_id) as searchable;
            // the base schema already carries them, and Typesense rejects a
            // schema with duplicate field names.
            if (in_array($column, $reserved, true)) {
                continue;
            }

            $titleLike = $this->isTitleLikeSearchColumn($column);
            $identifier = $this->isIdentifierSearchColumn($column);
            // Every projected column is nullable in the source table, so each
            // one is optional in the collection. Only the document identity is
            // required; a required field would reject the whole import batch
            // for one row with a null description.
            $field = ['name' => $column, 'type' => 'string', 'optional' => true];
            if (! $identifier) {
                $field['locale'] = 'ko';
            }
            if ($titleLike || $identifier) {
                $field['infix'] = true;
            }
            $fields[] = $field;
            if ($titleLike) {
                $fields[] = ['name' => $column.'_compact', 'type' => 'string', 'optional' => true, 'locale' => 'ko', 'infix' => true];
                // Compatibility jamo must remain literal characters. The
                // Korean tokenizer decomposes its own syllable input, which
                // would otherwise discard the already-decomposed shadow.
                $fields[] = ['name' => $column.'_jamo', 'type' => 'string', 'optional' => true, 'infix' => true];
            }
        }

        return ['name' => $this->searchableAs(), 'fields' => $fields];
    }

    /** @return array<string, string> */
    public function typesenseSearchParameters(): array
    {
        $fields = [];
        $typos = [];
        $infix = [];
        $weights = [];

        foreach ($this->externalSearchableColumns() as $column) {
            $titleLike = $this->isTitleLikeSearchColumn($column);
            $identifier = $this->isIdentifierSearchColumn($column);
            $fields[] = $column;
            $typos[] = $identifier ? '0' : '1';
            $infix[] = ($titleLike || $identifier) ? 'fallback' : 'off';
            $weights[] = '10';

            if ($titleLike) {
                $fields[] = $column.'_compact';
                $typos[] = '1';
                $infix[] = 'fallback';
                $weights[] = '8';
                $fields[] = $column.'_jamo';
                $typos[] = '0';
                $infix[] = 'fallback';
                // A compatibility-jamo hit is an incremental-input fallback,
                // never a substitute for an exact or infix title match.
                $weights[] = '1';
            }
        }

        return [
            'query_by' => implode(',', $fields),
            'num_typos' => implode(',', $typos),
            'min_len_1typo' => '3',
            'infix' => implode(',', $infix),
            'query_by_weights' => implode(',', $weights),
        ];
    }

    /**
     * External indexes may expose a deliberately declared presentation field
     * backed by a relation; the database list-search catalog stays restricted
     * to real columns.
     *
     * A model without the Filterable list-search catalog has no external
     * projection. Scout still resolves the collection for such a model on the
     * removal path, so this must answer with an empty catalog rather than
     * forwarding an undefined method call.
     *
     * @return list<string>
     */
    protected function externalSearchableColumns(): array
    {
        return $this->resourceListSearchableColumns();
    }

    /** @return list<string> */
    private function resourceListSearchableColumns(): array
    {
        if (! method_exists($this, 'resourceListFields')) {
            return [];
        }

        $fields = $this->resourceListFields();
        if (! $fields instanceof ResourceListFields) {
            throw new LogicException(sprintf(
                '%s::resourceListFields() must return %s.',
                static::class,
                ResourceListFields::class,
            ));
        }

        return $fields->searchableColumns();
    }

    protected function externalSearchableValue(string $column): mixed
    {
        return $this->getAttribute($column);
    }

    /**
     * Expand Hangul syllables to compatibility jamo for incremental input.
     * Non-Hangul text stays searchable too, normalized to lowercase without
     * introducing spaces between the decomposed syllables.
     */
    public static function decomposeHangulJamo(string $value): string
    {
        $initials = ['ㄱ', 'ㄲ', 'ㄴ', 'ㄷ', 'ㄸ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅃ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅉ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ'];
        $medials = ['ㅏ', 'ㅐ', 'ㅑ', 'ㅒ', 'ㅓ', 'ㅔ', 'ㅕ', 'ㅖ', 'ㅗ', 'ㅘ', 'ㅙ', 'ㅚ', 'ㅛ', 'ㅜ', 'ㅝ', 'ㅞ', 'ㅟ', 'ㅠ', 'ㅡ', 'ㅢ', 'ㅣ'];
        $finals = ['', 'ㄱ', 'ㄲ', 'ㄳ', 'ㄴ', 'ㄵ', 'ㄶ', 'ㄷ', 'ㄹ', 'ㄺ', 'ㄻ', 'ㄼ', 'ㄽ', 'ㄾ', 'ㄿ', 'ㅀ', 'ㅁ', 'ㅂ', 'ㅄ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ'];

        $result = '';
        foreach (mb_str_split(mb_strtolower($value)) as $character) {
            $codePoint = mb_ord($character);
            if ($codePoint < 0xAC00 || $codePoint > 0xD7A3) {
                $result .= $character;

                continue;
            }

            $offset = $codePoint - 0xAC00;
            $result .= $initials[intdiv($offset, 588)]
                .$medials[intdiv($offset % 588, 28)]
                .$finals[$offset % 28];
        }

        return $result;
    }

    /**
     * Build the query the Scout `database` engine runs to collect candidate
     * keys. Soft-deleted rows are INCLUDED so the caller's own Eloquent
     * query stays the sole authority on soft-delete visibility: default
     * list scopes still exclude trashed rows via the SoftDeletes global
     * scope, while admin `withTrashed()->filters()` endpoints (the user
     * directory and enterprise-structure management) can still surface deactivated
     * rows by search so they can be restored. This query remains the native
     * Scout path for direct model searches and database-search features such
     * as full-text/prefix attributes; ordinary Filterable database searches
     * apply the equivalent predicate directly to the caller's scoped query.
     */
    public function newScoutQuery(?ScoutBuilder $builder = null): Builder
    {
        $query = $this->newQuery();

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $query->withTrashed();
        }

        return $query;
    }
}
