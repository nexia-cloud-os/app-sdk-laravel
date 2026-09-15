<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** The only v1 purpose for owner-authorized protected document-data access. */
enum SignatureDocumentDataPurpose: string
{
    case SignatureRequestPreparation = 'signature_request_preparation';
}
