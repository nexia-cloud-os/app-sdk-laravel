<?php

declare(strict_types=1);

namespace Nexia\Signature;

/**
 * Internal, value-free diagnostics. These are deliberately separate from the
 * external source status vocabulary to avoid turning a response into an
 * authorization or record-existence probe.
 */
enum SignatureDocumentDataDiagnosticCode: string
{
    case Forbidden = 'forbidden';
    case InvalidProviderResult = 'invalid_provider_result';
    case Timeout = 'timeout';
    case VersionMismatch = 'version_mismatch';
}
