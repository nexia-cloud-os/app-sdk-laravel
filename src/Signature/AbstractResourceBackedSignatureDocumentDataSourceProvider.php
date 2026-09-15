<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\AppDescriptors\SignatureDocumentDataFieldDescriptor;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Signature\Contracts\SignatureDocumentDataSourceProvider;

/**
 * Narrow base for an App-owned source whose already-authorized resource query
 * resolves directly to protected document data.
 *
 * This deliberately does not contain a model, relation, policy, or query
 * builder. An App must explicitly perform every authorization, scope,
 * subject-relationship, and as-of/state check below before its bounded lookup.
 * Sources that need candidate selection or a non-resolved outcome should
 * implement SignatureDocumentDataSourceProvider directly.
 */
abstract class AbstractResourceBackedSignatureDocumentDataSourceProvider implements SignatureDocumentDataSourceProvider
{
    /** @var array<string, true> */
    private array $declaredProtectedFieldKeys;

    /**
     * @param  non-empty-list<string>  $declaredProtectedFieldKeys
     */
    public function __construct(
        private readonly string $appKey,
        private readonly string $sourceKey,
        private readonly int $sourceVersion,
        private readonly SignatureDocumentDataCardinality $cardinality,
        array $declaredProtectedFieldKeys,
        private readonly int $maximumResultCount,
    ) {
        if (! ResourceRef::hasCanonicalIdentity($appKey, $sourceKey) || $sourceVersion < 1) {
            throw new InvalidArgumentException('Resource-backed signature document data source identity is invalid.');
        }
        if ($maximumResultCount < 1 || $maximumResultCount > SignatureDocumentDataLimits::MAX_LIST_ITEMS) {
            throw new InvalidArgumentException('Resource-backed signature document data source result limit is invalid.');
        }
        if ($cardinality === SignatureDocumentDataCardinality::One && $maximumResultCount !== 1) {
            throw new InvalidArgumentException('Resource-backed signature document data source result limit must match its cardinality.');
        }

        $fields = [];
        foreach ($declaredProtectedFieldKeys as $fieldKey) {
            if (! is_string($fieldKey)
                || ! SignatureDocumentDataFieldDescriptor::isCanonicalKey($fieldKey)
                || isset($fields[$fieldKey])) {
                throw new InvalidArgumentException('Resource-backed signature document data source protected fields must be unique canonical field keys.');
            }
            $fields[$fieldKey] = true;
        }
        if (! array_is_list($declaredProtectedFieldKeys) || $fields === []) {
            throw new InvalidArgumentException('Resource-backed signature document data source protected fields must be a non-empty list.');
        }

        $this->declaredProtectedFieldKeys = $fields;
    }

    final public function appKey(): string
    {
        return $this->appKey;
    }

    final public function sourceKey(): string
    {
        return $this->sourceKey;
    }

    final public function sourceVersion(): int
    {
        return $this->sourceVersion;
    }

    final public function resolve(SignatureDocumentDataQuery $query): SignatureDocumentDataResult
    {
        if ($query->appKey !== $this->appKey
            || $query->sourceKey !== $this->sourceKey
            || $query->sourceVersion !== $this->sourceVersion) {
            throw new InvalidArgumentException('Resource-backed signature document data query does not match this source.');
        }
        foreach ($query->requestedFieldKeys as $fieldKey) {
            if (! isset($this->declaredProtectedFieldKeys[$fieldKey])) {
                throw new InvalidArgumentException("Resource-backed signature document data query requests undeclared protected field [{$fieldKey}].");
            }
        }

        // These checks have no SDK default: the owning App supplies the policy.
        $this->assertActorAuthorized($query);
        $this->assertTenantAndLegalEntityScope($query);
        $this->assertSubjectRelationship($query);
        $this->assertAsOfAndState($query);

        $resources = $this->resolveBoundedResources($query, $this->maximumResultCount);
        if (! array_is_list($resources)
            || $resources === []
            || count($resources) > $this->maximumResultCount) {
            throw new InvalidArgumentException('Resource-backed signature document data lookup must return a bounded non-empty list.');
        }

        $resolvedItems = [];
        foreach ($resources as $resource) {
            if (! $resource instanceof ResourceBackedSignatureDocumentData) {
                throw new InvalidArgumentException('Resource-backed signature document data lookup must return resource data DTOs.');
            }
            $this->assertResourceValuesMatchQuery($resource, $query);
            $resolvedItems[] = $resource->toResolvedItem();
        }

        $this->assertCardinalityAndSelectedReferences($resolvedItems, $query);

        return new SignatureDocumentDataResult(
            status: SignatureDocumentDataStatus::Resolved,
            resolvedItems: $resolvedItems,
        );
    }

    /** The owning App must authorize query->actor for this specific lookup. */
    abstract protected function assertActorAuthorized(SignatureDocumentDataQuery $query): void;

    /** The owning App must validate query->tenant and query->legalEntity scope. */
    abstract protected function assertTenantAndLegalEntityScope(SignatureDocumentDataQuery $query): void;

    /** The owning App must validate the source resource's relationship to the subject. */
    abstract protected function assertSubjectRelationship(SignatureDocumentDataQuery $query): void;

    /** The owning App must validate as-of semantics and any source state. */
    abstract protected function assertAsOfAndState(SignatureDocumentDataQuery $query): void;

    /**
     * Perform an explicit bounded App-owned lookup. Do not infer a latest or
     * current resource: selectedResourceRefs, when present, must be queried
     * exactly and are verified again by the base class.
     *
     * @return non-empty-list<ResourceBackedSignatureDocumentData>
     */
    abstract protected function resolveBoundedResources(
        SignatureDocumentDataQuery $query,
        int $maximumResultCount,
    ): array;

    private function assertResourceValuesMatchQuery(
        ResourceBackedSignatureDocumentData $resource,
        SignatureDocumentDataQuery $query,
    ): void {
        $values = $resource->protectedValues();
        $requested = array_fill_keys($query->requestedFieldKeys, true);
        if (array_diff_key($values, $requested) !== [] || array_diff_key($requested, $values) !== []) {
            throw new InvalidArgumentException('Resource-backed signature document data must return exactly the requested protected fields.');
        }
    }

    /** @param list<SignatureDocumentDataResolvedItem> $resolvedItems */
    private function assertCardinalityAndSelectedReferences(array $resolvedItems, SignatureDocumentDataQuery $query): void
    {
        if ($this->cardinality === SignatureDocumentDataCardinality::One && count($resolvedItems) !== 1) {
            throw new InvalidArgumentException('A one-cardinality resource-backed signature document data source must resolve exactly one resource.');
        }
        if ($query->selectedResourceRefs === []) {
            return;
        }

        $selected = [];
        foreach ($query->selectedResourceRefs as $reference) {
            $selected[self::resourceIdentity($reference)] = true;
        }
        $resolved = [];
        foreach ($resolvedItems as $item) {
            $resolved[self::resourceIdentity($item->resourceRef)] = true;
        }
        if (count($selected) !== count($resolved)
            || array_diff_key($selected, $resolved) !== []
            || array_diff_key($resolved, $selected) !== []) {
            throw new InvalidArgumentException('Resource-backed signature document data selected references must be revalidated exactly.');
        }
    }

    private static function resourceIdentity(ResourceRef $reference): string
    {
        return $reference->appKey."\0".$reference->resourceKey."\0".$reference->resourceId;
    }
}
