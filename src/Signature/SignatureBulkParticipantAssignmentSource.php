<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** The authority that supplies one participant slot in a bulk request group. */
enum SignatureBulkParticipantAssignmentSource: string
{
    case RequestSupplied = 'request_supplied';
    case TemplateFixed = 'template_fixed';
    case BindingResolved = 'binding_resolved';
}
