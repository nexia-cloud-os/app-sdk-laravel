<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain;

use RuntimeException;

final class ApprovalException extends RuntimeException
{
    /**
     * Stable, locale-independent error code for host error rendering.
     */
    public ?string $errorCode = null;

    /**
     * Non-technical interpolation values for a localized error template.
     *
     * @var array<string, mixed>
     */
    public array $errorParams = [];

    /** Host-owned preparation context; never included in the public error payload. */
    public ?\Nexia\ResourceReference\ResourceRef $preparationResourceRef = null;

    public static function invalidTransition(string $state, string $transition): self
    {
        $exception = new self("Approval case in state [{$state}] cannot apply transition [{$transition}].");
        $exception->errorCode = 'transition_invalid';

        return $exception;
    }

    public static function inactiveActor(int $actorId): self
    {
        $exception = new self("Approval actor [{$actorId}] is not active.");
        $exception->errorCode = 'actor_inactive';

        return $exception;
    }

    public static function ineligibleActor(int $actorId): self
    {
        $exception = new self("Approval actor [{$actorId}] is not eligible for the active step.");
        $exception->errorCode = 'actor_ineligible';

        return $exception;
    }

    public static function nonActionableActor(int $actorId, int $legalEntityId): self
    {
        $exception = new self("Approval actor [{$actorId}] cannot act in Legal Entity [{$legalEntityId}].");
        $exception->errorCode = 'actor_nonactionable';

        return $exception;
    }

    public static function unreadableRecipient(int $actorId, int $legalEntityId): self
    {
        $exception = new self("Approval recipient [{$actorId}] cannot read cases in Legal Entity [{$legalEntityId}].");
        $exception->errorCode = 'recipient_unreadable';

        return $exception;
    }

    public static function idempotencyConflict(): self
    {
        $exception = new self('Idempotency-Key was already used with a different approval action payload.', 409);
        $exception->errorCode = 'idempotency_conflict';

        return $exception;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function operationFailed(string $message, string $code, array $params = [], int $status = 0): self
    {
        $exception = new self($message, $status);
        $exception->errorCode = $code;
        $exception->errorParams = $params;

        return $exception;
    }

    /**
     * @return array{code: string|null, params: array<string, mixed>}
     */
    public function toErrorPayload(): array
    {
        return [
            'code' => $this->errorCode,
            'params' => $this->errorParams,
        ];
    }
}
