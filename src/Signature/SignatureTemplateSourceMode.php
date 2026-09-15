<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureTemplateSourceMode: string
{
    case UploadedPdf = 'uploaded_pdf';
    case AuthoredDocument = 'authored_document';
}
