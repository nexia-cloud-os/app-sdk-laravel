<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;

/** App-neutral input envelope for one bulk document group. */
final readonly class SignatureBulkGroupSelection
{
    public const int MAX_CLIENT_KEY_LENGTH = 191;

    public function __construct(
        public string $clientKey,
        public ?ResourceRef $selectionRef = null,
    ) {
        if ($clientKey === ''
            || $clientKey !== trim($clientKey)
            || strlen($clientKey) > self::MAX_CLIENT_KEY_LENGTH) {
            throw new InvalidArgumentException('Signature bulk group client_key must be normalized, non-blank, and bounded.');
        }
    }

    /** @return array{client_key:string,selection_ref:array{app_key:string,resource_key:string,resource_id:string}|null} */
    public function toArray(): array
    {
        return [
            'client_key' => $this->clientKey,
            'selection_ref' => $this->selectionRef === null ? null : self::resourceIdentity($this->selectionRef),
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, ['client_key', 'selection_ref']);
        $selection = $data['selection_ref'];
        if ($selection !== null && ! is_array($selection)) {
            throw new InvalidArgumentException('Serialized signature bulk selection_ref must be null or an object.');
        }

        return new self(
            clientKey: SignatureBulkTemplateParticipantAssignment::string($data, 'client_key'),
            selectionRef: $selection === null ? null : self::resourceFromIdentity($selection),
        );
    }

    /** @return array{app_key:string,resource_key:string,resource_id:string} */
    public static function resourceIdentity(ResourceRef $reference): array
    {
        return [
            'app_key' => $reference->appKey,
            'resource_key' => $reference->resourceKey,
            'resource_id' => $reference->resourceId,
        ];
    }

    /** @param array<string,mixed> $data */
    public static function resourceFromIdentity(array $data): ResourceRef
    {
        SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, ['app_key', 'resource_key', 'resource_id']);

        return new ResourceRef(
            appKey: SignatureBulkTemplateParticipantAssignment::string($data, 'app_key'),
            resourceKey: SignatureBulkTemplateParticipantAssignment::string($data, 'resource_key'),
            resourceId: SignatureBulkTemplateParticipantAssignment::string($data, 'resource_id'),
            display: '',
        );
    }
}
