<?php

declare(strict_types=1);

namespace Nexia\Contribution;

use InvalidArgumentException;
use Nexia\Permission\AssignmentScope;

final readonly class ResourceAuthorizationContract
{
    private function __construct(
        public ResourceRecordOwner $recordOwner,
        public AssignmentScope $permissionScope,
        public ResourceLegalEntityParticipation $legalEntityParticipation,
        public ResourceVisibilityProfile $visibilityProfile,
    ) {
        if ($recordOwner === ResourceRecordOwner::Tenant
            && $legalEntityParticipation !== ResourceLegalEntityParticipation::None) {
            throw new InvalidArgumentException(
                'A tenant-owned Resource cannot use direct Legal Entity-owner participation.',
            );
        }

        if ($recordOwner === ResourceRecordOwner::LegalEntity
            && $legalEntityParticipation !== ResourceLegalEntityParticipation::RecordOwner) {
            throw new InvalidArgumentException(
                'A direct Legal Entity-owned Resource must identify its record owner as the Legal Entity participant.',
            );
        }
    }

    public static function standard(
        ResourceRecordOwner $recordOwner,
        AssignmentScope $permissionScope,
        ResourceLegalEntityParticipation $legalEntityParticipation,
    ): self {
        return new self(
            $recordOwner,
            $permissionScope,
            $legalEntityParticipation,
            ResourceVisibilityProfile::Standard,
        );
    }

    public static function custom(
        ResourceRecordOwner $recordOwner,
        AssignmentScope $permissionScope,
        ResourceLegalEntityParticipation $legalEntityParticipation,
    ): self {
        return new self(
            $recordOwner,
            $permissionScope,
            $legalEntityParticipation,
            ResourceVisibilityProfile::Custom,
        );
    }
}
