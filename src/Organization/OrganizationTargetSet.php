<?php

declare(strict_types=1);

namespace Nexia\Organization;

/** Exact host-authorized organization targets; never a Legal Entity × Operating Unit product. */
final readonly class OrganizationTargetSet
{
    /** @param list<OrganizationTarget> $targets */
    public function __construct(public array $targets) {}

    public function filter(OrganizationTargetQuery $query): self
    {
        if ($query->isUnrestricted()) {
            return $this;
        }

        return new self(array_values(array_filter(
            $this->targets,
            static fn (OrganizationTarget $target): bool => (
                $query->legalEntityPublicIds === null
                || in_array(strtolower($target->legalEntity->publicId()), $query->legalEntityPublicIds, true)
            ) && (
                $query->operatingUnitPublicIds === null
                || ($target->operatingUnit !== null
                    && in_array(strtolower($target->operatingUnit->publicId()), $query->operatingUnitPublicIds, true))
            ),
        )));
    }
}
