<?php

declare(strict_types=1);

namespace Nexia\Approval;

/**
 * Whether a tenant has actually made an App's business form fileable.
 *
 * Contributing a binding descriptor does not create a live form. An
 * administrator still has to author a template against that binding, set its
 * approval line, and publish it. An App that gates a policy on "employees can
 * really file this" needs to ask about that published state rather than assume
 * its own descriptor is enough — and it must not read Core tables to find out.
 */
final readonly class ApprovalBindingAvailability
{
    public function __construct(
        public string $bindingKey,
        public bool $hasPublishedTemplate,
        public ?string $templateKey = null,
        public ?int $templateVersion = null,
        public ?string $templateName = null,
        public bool $rejectRequiresComment = false,
        public bool $hasRouteConstraint = false,
        public bool $hasUsableLine = true,
    ) {}

    public static function unavailable(string $bindingKey): self
    {
        return new self($bindingKey, false);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'binding_key' => $this->bindingKey,
            'has_published_template' => $this->hasPublishedTemplate,
            'template_key' => $this->templateKey,
            'template_version' => $this->templateVersion,
            'template_name' => $this->templateName,
            'reject_requires_comment' => $this->rejectRequiresComment,
            'has_route_constraint' => $this->hasRouteConstraint,
            'has_usable_line' => $this->hasUsableLine,
        ];
    }
}
