<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureCapabilityUnavailableReason: string
{
    case SmsTransportUnconfigured = 'sms_transport_unconfigured';
    case LocalSealerUnconfigured = 'local_sealer_unconfigured';
    case LocalDeliveryUnavailable = 'local_delivery_unavailable';
    case LocalAuthenticationUnavailable = 'local_authentication_unavailable';
    case RoutingModeUnavailable = 'routing_mode_unavailable';
}
