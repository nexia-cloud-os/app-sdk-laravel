<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Stable, non-sensitive failures a binding provider may return to Core. */
enum SignatureBulkBindingFailureReason: string
{
    case SelectionRequired = 'selection_required';
    case SelectionForbidden = 'selection_forbidden';
    case SubjectUnavailable = 'subject_unavailable';
    case SubjectUnauthorized = 'subject_unauthorized';
    case SubjectStateInvalid = 'subject_state_invalid';
    case ParticipantUnavailable = 'participant_unavailable';
    case ParticipantUnauthorized = 'participant_unauthorized';
    case AssignmentIncomplete = 'assignment_incomplete';
    case AssignmentDuplicate = 'assignment_duplicate';
    case AuthorizationRevoked = 'authorization_revoked';
    case ProviderUnavailable = 'provider_unavailable';
    case ProviderIdentityMismatch = 'provider_identity_mismatch';
    case ProviderVersionMismatch = 'provider_version_mismatch';
    case CapabilityVersionMismatch = 'capability_version_mismatch';
    case InternalFailure = 'internal_failure';
}
