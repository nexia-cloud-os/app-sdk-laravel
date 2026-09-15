<?php

declare(strict_types=1);

namespace Nexia\Approval\Resolver;

use Nexia\Approval\Contracts\ResourceSubjectActor;
use InvalidArgumentException;

/**
 * Author-facing declaration for a {@see ResourceSubjectActor}.
 *
 * `subjectLabelKey` is not decoration. A route policy is reusable across
 * resources, so "the subject" means a different person per record — the
 * requisition's owner here, the worker taking leave there. An administrator
 * choosing a subject-anchored step must see which person that is for this
 * resource, or the choice is a guess. The route-policy editor renders this
 * label beside the anchor option.
 */
final readonly class ResourceSubjectActorDescriptor
{
    public function __construct(
        /** The resource this provider answers for, e.g. `sample-workflow.job_offer_revision`. */
        public string $resourceKey,
        /** Owning App key; Core filters by installed-app operational state. */
        public string $ownerAppKey,
        /** Names WHO the subject is for this resource, e.g. "채용 요구서 담당자". */
        public string $subjectLabelKey,
    ) {
        foreach ([
            'resourceKey' => $resourceKey,
            'ownerAppKey' => $ownerAppKey,
            'subjectLabelKey' => $subjectLabelKey,
        ] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException(
                    "Resource subject actor {$field} must be non-blank and normalized.",
                );
            }
        }
    }

    /** @return array{resource_key: string, owner_app_key: string, subject_label_key: string} */
    public function toArray(): array
    {
        return [
            'resource_key' => $this->resourceKey,
            'owner_app_key' => $this->ownerAppKey,
            'subject_label_key' => $this->subjectLabelKey,
        ];
    }
}
