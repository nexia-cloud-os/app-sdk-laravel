<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/**
 * App-contributed Approval form domain binding.
 *
 * A binding declares one App-owned business action available through the
 * host-owned Approval composer. It names integration points only: the App
 * remains responsible for validating and persisting its payload, while the
 * host authorizes only contributed, installed, non-removed descriptors.
 */
final class ApprovalFormBindingDescriptor implements AppDescriptor
{
    /** Supported ways a submission can enter the bound resource. */
    public const ENTRY_MODES = ['create', 'link'];

    /** Composite descriptor key `{appKey}.{resourceKey}.{actionKey}`. */
    public readonly string $key;

    /**
     * @param  list<string>  $entryModes
     */
    public function __construct(
        public readonly string $appKey,
        public readonly string $resourceKey,
        public readonly string $actionKey,
        public readonly string $labelKey,
        public readonly array $entryModes,
        public readonly string $formWidgetKey,
        public readonly string $documentSchemaKey,
        public readonly string $version = '1.0',
        public readonly DescriptorStatus $status = DescriptorStatus::Active,
        public readonly ?string $descriptionKey = null,
        public readonly ?string $approvedLabelKey = null,
        public readonly ?string $rejectedLabelKey = null,
        // Recall and cancellation are outcomes of this binding just as approval
        // and rejection are. Without these, an App can name the first two and
        // the host has to fall back to a subject-less platform label for the
        // other two, so every binding in an App contributes an identically
        // named recalled/cancelled trigger.
        public readonly ?string $recalledLabelKey = null,
        public readonly ?string $cancelledLabelKey = null,
        public readonly ?string $submitPermissionKey = null,
    ) {
        if (trim($appKey) === '') {
            throw new \InvalidArgumentException('ApprovalFormBindingDescriptor app_key must be a non-empty string.');
        }
        if (trim($resourceKey) === '') {
            throw new \InvalidArgumentException("ApprovalFormBindingDescriptor [{$appKey}] resource_key must be a non-empty string.");
        }
        if (trim($actionKey) === '') {
            throw new \InvalidArgumentException("ApprovalFormBindingDescriptor [{$appKey}.{$resourceKey}] action_key must be a non-empty string.");
        }

        $id = "{$appKey}.{$resourceKey}.{$actionKey}";

        if (trim($labelKey) === '') {
            throw new \InvalidArgumentException("ApprovalFormBindingDescriptor [{$id}] label_key must be a non-empty string.");
        }
        if ($entryModes === []) {
            throw new \InvalidArgumentException("ApprovalFormBindingDescriptor [{$id}] must declare at least one entry mode.");
        }
        foreach ($entryModes as $mode) {
            if (! in_array($mode, self::ENTRY_MODES, true)) {
                throw new \InvalidArgumentException(
                    "ApprovalFormBindingDescriptor [{$id}] entry mode [{$mode}] must be one of: ".implode(', ', self::ENTRY_MODES),
                );
            }
        }
        if (trim($formWidgetKey) === '') {
            throw new \InvalidArgumentException("ApprovalFormBindingDescriptor [{$id}] form_widget_key must be a non-empty string.");
        }
        if (trim($documentSchemaKey) === '') {
            throw new \InvalidArgumentException("ApprovalFormBindingDescriptor [{$id}] document_schema_key must be a non-empty string.");
        }
        if (trim($version) === '') {
            throw new \InvalidArgumentException("ApprovalFormBindingDescriptor [{$id}] version must be a non-empty string.");
        }
        if ($submitPermissionKey !== null && trim($submitPermissionKey) === '') {
            throw new \InvalidArgumentException("ApprovalFormBindingDescriptor [{$id}] submit_permission_key must be a non-empty string or null.");
        }

        $this->key = trim($appKey).'.'.trim($resourceKey).'.'.trim($actionKey);
    }

    public function supportsEntryMode(string $mode): bool
    {
        return in_array($mode, $this->entryModes, true);
    }

    public function submitPermission(): string
    {
        return $this->submitPermissionKey ?? $this->key;
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
