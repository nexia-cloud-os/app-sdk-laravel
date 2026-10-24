<?php

declare(strict_types=1);

namespace Nexia\DocumentData\Contracts;

use Nexia\Identity\Contracts\Actor;
use Nexia\Signature\SignatureDocumentDataQuery;
use Nexia\Signature\SignatureDocumentDataResult;

/** Read-only discovery and owner-authorized resolution. Values are never cached. */
interface DocumentDataHost
{
    /**
     * Static metadata only. Filters: purpose, subject_resource_key, app_key,
     * source_key, source_version, field_key/field_keys, q, offset, limit, automatic_only, stage.
     * @return array{data: list<array<string,mixed>>, next_offset: ?int}
     */
    public function catalog(Actor $actor, array $filters = []): array;

    public function resolve(SignatureDocumentDataQuery $query): SignatureDocumentDataResult;
}
