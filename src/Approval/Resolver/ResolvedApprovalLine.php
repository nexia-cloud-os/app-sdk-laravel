<?php

declare(strict_types=1);

namespace Nexia\Approval\Resolver;

use Nexia\Approval\Domain\ApprovalException;
use Nexia\Approval\Domain\ApprovalLineDefinition;
use Nexia\Approval\Domain\ApprovalLineStepDefinition;

final readonly class ResolvedApprovalLine
{
    /** @param list<list<ApprovalLineStepDefinition>>|null $stages @param list<ResolutionError> $errors */
    private function __construct(private ?array $stages, private array $errors) {}

    /** @param list<list<ApprovalLineStepDefinition>> $stages */
    public static function resolved(array $stages): self
    {
        try {
            ApprovalLineDefinition::fromStages($stages);
        } catch (ApprovalException $exception) {
            return self::failed([new ResolutionError(ResolutionErrorCode::Unresolved, $exception->getMessage())]);
        }

        return new self($stages, []);
    }

    /** @param list<ResolutionError> $errors */
    public static function failed(array $errors): self
    {
        return new self(null, array_values($errors));
    }

    public function isResolved(): bool
    {
        return $this->stages !== null;
    }

    /** @return list<list<ApprovalLineStepDefinition>> */
    public function stages(): array
    {
        return $this->stages ?? [];
    }

    /** @return list<ResolutionError> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?ResolutionError
    {
        return $this->errors[0] ?? null;
    }

    public function hasCategory(string $category): bool
    {
        foreach ($this->errors as $error) {
            if ($error->category() === $category) {
                return true;
            }
        }

        return false;
    }
}
