<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Signature\SignatureDocumentDataQuery;
use Nexia\Signature\SignatureDocumentDataResult;

/**
 * App-owned protected data lookup. The same method handles initial candidate
 * lookup and exact selected-resource revalidation through the query's
 * selectedResourceRefs, so Core never reconstructs owner selection rules.
 */
interface SignatureDocumentDataSourceProvider
{
    public function appKey(): string;

    public function sourceKey(): string;

    public function sourceVersion(): int;

    public function resolve(SignatureDocumentDataQuery $query): SignatureDocumentDataResult;
}
