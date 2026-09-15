<?php

declare(strict_types=1);

namespace Nexia\Signature;

use Nexia\Signature\Contracts\SignatureDocumentPlanSource;
use InvalidArgumentException;

/** Published template plus the App-owned values and business-role snapshot. */
final readonly class PublishedTemplateSignatureDocumentSource implements SignatureDocumentPlanSource
{
    /**
     * @param array<string, mixed> $variables
     * @param array<string, list<array<string, scalar|null>>> $signatoryRoles
     */
    public function __construct(
        public string $templateKey,
        public array $variables,
        public array $signatoryRoles,
    ) {
        if ($templateKey === '' || $templateKey !== trim($templateKey)
            || ($variables !== [] && array_is_list($variables))
            || array_is_list($signatoryRoles)
            || $signatoryRoles === []) {
            throw new InvalidArgumentException('Published template signature document source is invalid.');
        }
    }

    public function kind(): SignatureDocumentSourceKind
    {
        return SignatureDocumentSourceKind::PublishedTemplate;
    }
}
