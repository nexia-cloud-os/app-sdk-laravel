<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureCapabilityKind: string
{
    case InvitationChannel = 'invitation_channel';
    case AuthenticationMethod = 'authentication_method';
    case DocumentSealer = 'document_sealer';
    case RoutingMode = 'routing_mode';
}
