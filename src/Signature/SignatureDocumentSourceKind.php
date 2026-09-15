<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureDocumentSourceKind: string
{
    case PublishedTemplate = 'published_template';
    case UploadedPdf = 'uploaded_pdf';
}
