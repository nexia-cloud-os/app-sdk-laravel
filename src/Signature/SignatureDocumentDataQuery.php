<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\AppDescriptors\SignatureDocumentDataFieldDescriptor;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Tenancy\Contracts\TenantIdentity;

/**
 * Core-restored context for one owner-authorized document-data lookup or
 * selected-reference revalidation. This carries interfaces only; it exposes
 * no Core model, query builder, or persistence service.
 */
final readonly class SignatureDocumentDataQuery
{
    /**
     * @param  array<string, ResourceRef>  $derivedSubjectAnchors
     * @param  non-empty-list<string>  $requestedFieldKeys
     * @param  list<ResourceRef>  $selectedResourceRefs
     */
    public function __construct(
        public TenantIdentity $tenant,
        public LegalEntity $legalEntity,
        public Actor $actor,
        public SignatureDocumentDataPurpose $purpose,
        public string $appKey,
        public string $sourceKey,
        public int $sourceVersion,
        public ResourceRef $subjectResourceRef,
        public array $derivedSubjectAnchors,
        public ?ResourceRef $explicitSourceRef,
        public DateTimeImmutable $asOf,
        public array $requestedFieldKeys,
        public array $selectedResourceRefs = [],
    ) {
        if (preg_match('/\A[a-z][a-z0-9]*(?:-[a-z0-9]+)*\z/D', $appKey) !== 1
            || ! ResourceRef::hasCanonicalIdentity($appKey, $sourceKey)
            || $sourceVersion < 1) {
            throw new InvalidArgumentException('Signature document data query source identity is invalid.');
        }

        if (($derivedSubjectAnchors !== [] && array_is_list($derivedSubjectAnchors))
            || count($derivedSubjectAnchors) > SignatureDocumentDataLimits::MAX_SOURCE_BINDINGS) {
            throw new InvalidArgumentException('Signature document data query derived subject anchors must be a bounded object.');
        }
        foreach ($derivedSubjectAnchors as $key => $reference) {
            if (! is_string($key)
                || preg_match('/\A[a-z][a-z0-9_]*\z/D', $key) !== 1
                || ! $reference instanceof ResourceRef) {
                throw new InvalidArgumentException('Signature document data query derived subject anchors must be canonical ResourceRef values.');
            }
        }

        if (! array_is_list($requestedFieldKeys)
            || $requestedFieldKeys === []
            || count($requestedFieldKeys) > SignatureDocumentDataLimits::MAX_RESOLVED_ITEM_FIELDS) {
            throw new InvalidArgumentException('Signature document data query requested fields must be a bounded non-empty list.');
        }
        $requested = [];
        foreach ($requestedFieldKeys as $fieldKey) {
            if (! is_string($fieldKey)
                || ! SignatureDocumentDataFieldDescriptor::isCanonicalKey($fieldKey)
                || isset($requested[$fieldKey])) {
                throw new InvalidArgumentException('Signature document data query requested fields must be unique canonical field keys.');
            }
            $requested[$fieldKey] = true;
        }

        if (! array_is_list($selectedResourceRefs)
            || count($selectedResourceRefs) > SignatureDocumentDataLimits::MAX_LIST_ITEMS) {
            throw new InvalidArgumentException('Signature document data query selected resource references must be a bounded list.');
        }
        $selected = [];
        foreach ($selectedResourceRefs as $reference) {
            if (! $reference instanceof ResourceRef) {
                throw new InvalidArgumentException('Signature document data query selected resource references must be ResourceRef values.');
            }
            $identity = $reference->appKey."\0".$reference->resourceKey."\0".$reference->resourceId;
            if (isset($selected[$identity])) {
                throw new InvalidArgumentException('Signature document data query selected resource references must be unique.');
            }
            $selected[$identity] = true;
        }
    }
}
