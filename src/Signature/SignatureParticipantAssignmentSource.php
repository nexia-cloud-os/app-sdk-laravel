<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** The authority that supplies one participant identity for a template role and slot. */
enum SignatureParticipantAssignmentSource: string
{
    case BindingResolved = 'binding_resolved';
    case RequestSupplied = 'request_supplied';
    case TemplateFixed = 'template_fixed';
}
