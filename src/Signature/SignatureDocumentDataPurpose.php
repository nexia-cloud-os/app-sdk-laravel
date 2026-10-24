<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Explicit consumer purpose; adding a consumer never grants access to a source. */
enum SignatureDocumentDataPurpose: string
{
    case SignatureRequestPreparation = 'signature_request_preparation';
    case DocumentPreparation = 'document_preparation';
}
