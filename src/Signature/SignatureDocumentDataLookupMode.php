<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** App-owned lookup rule named by a signature document data source. */
enum SignatureDocumentDataLookupMode: string
{
    case DirectSubject = 'direct_subject';
    case DerivedRef = 'derived_ref';
    case ExplicitSourceRef = 'explicit_source_ref';
    case OwnerScopedQuery = 'owner_scoped_query';
}
