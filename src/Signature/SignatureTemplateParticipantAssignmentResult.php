<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Exact immutable template provenance and its contact-free participant policy. */
final readonly class SignatureTemplateParticipantAssignmentResult
{
    /** @param list<SignatureTemplateParticipantAssignment> $assignments */
    public function __construct(
        public string $templatePublicId,
        public int $templateVersion,
        public string $contentHash,
        public array $assignments,
    ) {
        if ($templatePublicId === ''
            || $templatePublicId !== trim($templatePublicId)
            || $templateVersion < 1
            || preg_match('/\A[0-9a-f]{64}\z/D', $contentHash) !== 1
            || ! array_is_list($assignments)) {
            throw new InvalidArgumentException('Signature template participant assignment result is invalid.');
        }
        $identities = [];
        foreach ($assignments as $assignment) {
            if (! $assignment instanceof SignatureTemplateParticipantAssignment) {
                throw new InvalidArgumentException('Signature template participant assignment result entries are invalid.');
            }
            $identity = $assignment->roleKey."\0".$assignment->participantSlot;
            if (isset($identities[$identity])) {
                throw new InvalidArgumentException('Signature template participant assignment result repeats an identity.');
            }
            $identities[$identity] = true;
        }
    }

    /** @return array{template_public_id:string,template_version:int,content_hash:string,assignments:list<array<string,mixed>>} */
    public function toArray(): array
    {
        return [
            'template_public_id' => $this->templatePublicId,
            'template_version' => $this->templateVersion,
            'content_hash' => $this->contentHash,
            'assignments' => array_map(
                static fn (SignatureTemplateParticipantAssignment $assignment): array => $assignment->toArray(),
                $this->assignments,
            ),
        ];
    }
}
