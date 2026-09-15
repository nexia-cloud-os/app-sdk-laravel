<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureErrorCode: string
{
    case Unauthorized = 'unauthorized';
    case BindingUnavailable = 'binding_unavailable';
    case TemplateUnavailable = 'template_unavailable';
    case DocumentNotReady = 'document_not_ready';
    case UnsupportedCapability = 'unsupported_capability';
    case PayloadDrift = 'payload_drift';
    case RequestConflict = 'request_conflict';
    case TokenInvalid = 'token_invalid';
    case TokenExpired = 'token_expired';
    case ChallengeLocked = 'challenge_locked';
    case AuthenticationFailed = 'authentication_failed';
    case RateLimited = 'rate_limited';
    case ExecutionUnavailable = 'execution_unavailable';
    case ArtifactVerificationFailed = 'artifact_verification_failed';
    case RequestNotFound = 'request_not_found';
    case RequestScopeMismatch = 'request_scope_mismatch';
    case DocumentReferenceMismatch = 'document_reference_mismatch';
    case ParticipantInvalid = 'participant_invalid';
    case ParticipantAssignmentInvalid = 'participant_assignment_invalid';
    case AuthenticationProfileUnavailable = 'authentication_profile_unavailable';
    case AuthenticationMethodsInvalid = 'authentication_methods_invalid';
    case ConsentPolicyUnavailable = 'consent_policy_unavailable';
    case ExpiryPolicyInvalid = 'expiry_policy_invalid';
    case RequestTerminal = 'request_terminal';
}
