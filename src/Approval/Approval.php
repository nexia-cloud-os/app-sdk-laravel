<?php

declare(strict_types=1);

namespace Nexia\Approval;

use Nexia\Approval\Contracts\ApprovalQueries;
use Nexia\Laravel\Directory\LegalEntity as DirectoryLegalEntity;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Organization\Contracts\OrganizationDirectory;

final class Approval
{
    public static function query(LegalEntity|DirectoryLegalEntity|string $legalEntity): ApprovalQuery
    {
        if (! $legalEntity instanceof LegalEntity) {
            $publicId = $legalEntity instanceof DirectoryLegalEntity ? (string) $legalEntity->public_id : $legalEntity;
            $legalEntity = app(OrganizationDirectory::class)->legalEntity($publicId);
        }

        return new ApprovalQuery(app(ApprovalQueries::class), $legalEntity);
    }
}
