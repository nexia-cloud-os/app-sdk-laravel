<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Signature\SignatureTemplateParticipantAssignmentQuery;
use Nexia\Signature\SignatureTemplateParticipantAssignmentResult;

/** Resolves the exact contact-free participant policy for one published template winner. */
interface SignatureTemplateParticipantAssignmentHost
{
    public function resolve(
        SignatureTemplateParticipantAssignmentQuery $query,
    ): SignatureTemplateParticipantAssignmentResult;
}
