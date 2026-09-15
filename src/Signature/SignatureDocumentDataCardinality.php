<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Cardinality declared by an App-owned signature document data source. */
enum SignatureDocumentDataCardinality: string
{
    case One = 'one';
    case Many = 'many';
}
