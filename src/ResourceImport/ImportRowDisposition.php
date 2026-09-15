<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

use InvalidArgumentException;

/** Safe, frozen row analysis returned by an App pipeline before apply. */
final readonly class ImportRowDisposition
{
    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const UNCHANGED = 'unchanged';

    public const CONFLICT = 'conflict';

    public const ERROR = 'error';

    /**
     * @param  array<string, string|null>  $values  masked values safe for preview
     * @param  list<string>  $fields  fields responsible for conflict/error
     * @param  list<array{field: string, before: string|null, after: string|null}>  $changes
     * @param  list<array{kind:string,code?:string,field:string,value:string,label_key:string,message_key:string,reference_kind?:string,reference_value?:string}>  $issues
     */
    public function __construct(
        public int $row,
        public string $status,
        public array $values,
        public array $fields = [],
        public array $changes = [],
        public ?string $invitationStatus = null,
        public array $issues = [],
    ) {
        if (! in_array($status, [self::CREATE, self::UPDATE, self::UNCHANGED, self::CONFLICT, self::ERROR], true)) {
            throw new InvalidArgumentException("Unknown import row disposition [{$status}].");
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'line' => $this->row,
            'status' => $this->status,
            'values' => $this->values,
            'fields' => $this->fields,
            'changes' => $this->changes,
            'invitation_status' => $this->invitationStatus,
            'issues' => $this->issues,
        ];
    }
}
