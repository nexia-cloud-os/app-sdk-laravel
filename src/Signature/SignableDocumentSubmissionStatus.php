<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Whether a document command created a row or reused its exact identity. */
enum SignableDocumentSubmissionStatus: string
{
    case Accepted = 'accepted';
    case Existing = 'existing';
}
