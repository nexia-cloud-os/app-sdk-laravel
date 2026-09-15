<?php

declare(strict_types=1);

namespace Nexia\Laravel\StateMachine;

use BackedEnum;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

/**
 * An App-owned state transition declared with enum cases rather than strings.
 *
 * @template TState of BackedEnum
 */
final readonly class EnumStateTransition
{
    /**
     * @param  list<TState>  $from
     * @param  TState|null  $to
     */
    private function __construct(
        public array $from,
        public ?BackedEnum $to,
        private bool $acceptsNull,
    ) {
        if ($this->from === []) {
            if (! $this->acceptsNull || $this->to !== null) {
                throw new InvalidArgumentException('A stateful enum transition requires source states.');
            }

            return;
        }

        $enumClass = ($this->to ?? $this->from[0])::class;

        foreach ($this->from as $state) {
            if (! $state instanceof $enumClass) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Enum transition to [%s] contains a source state from [%s].',
                        $enumClass,
                        $state::class,
                    ),
                );
            }
        }
    }

    /**
     * @template T of BackedEnum
     *
     * @param  non-empty-list<T>  $from
     * @param  T  $to
     * @return self<T>
     */
    public static function to(BackedEnum $to, array $from): self
    {
        if ($from === [] || ! array_is_list($from)) {
            throw new InvalidArgumentException('An enum transition requires a non-empty list of source states.');
        }

        foreach ($from as $state) {
            if (! $state instanceof BackedEnum) {
                throw new InvalidArgumentException('Enum transition source states must be backed-enum cases.');
            }
        }

        return new self($from, $to, false);
    }

    /**
     * Declare an enum-based guard that leaves state unchanged.
     *
     * @template T of BackedEnum
     *
     * @param  non-empty-list<T>  $from
     * @return self<T>
     */
    public static function guard(array $from): self
    {
        if ($from === [] || ! array_is_list($from)) {
            throw new InvalidArgumentException('An enum state guard requires a non-empty list of source states.');
        }

        foreach ($from as $state) {
            if (! $state instanceof BackedEnum) {
                throw new InvalidArgumentException('Enum state guard source states must be backed-enum cases.');
            }
        }

        return new self($from, null, false);
    }

    /** Declare a command that is valid only when the record has no state value. */
    public static function stateless(): self
    {
        return new self([], null, true);
    }

    /**
     * Assert that the persisted current state belongs to the declared source set.
     *
     * @param  TState|string|null  $current
     *
     * @throws ValidationException
     */
    public function assertAllowed(
        BackedEnum|string|null $current,
        string $attribute = 'state',
        ?string $message = null,
    ): void {
        if ($this->acceptsNull && $current === null) {
            return;
        }

        $enumClass = ($this->to ?? $this->from[0])::class;
        $currentValue = $current instanceof BackedEnum ? $current->value : $current;
        $currentCase = $current instanceof $enumClass
            ? $current
            : (is_string($currentValue) || is_int($currentValue) ? $enumClass::tryFrom($currentValue) : null);

        if ($currentCase !== null && in_array($currentCase, $this->from, true)) {
            return;
        }

        $from = implode(', ', array_map(
            static fn (BackedEnum $state): string => (string) $state->value,
            $this->from,
        ));
        $target = $this->to instanceof BackedEnum ? (string) $this->to->value : 'unchanged';
        $message ??= sprintf(
            'Transition from [%s] to [%s] is not allowed; expected one of [%s].',
            (string) $currentValue,
            $target,
            $from,
        );

        $validator = (new Factory(new Translator(new ArrayLoader, 'en')))->make([], []);
        $validator->errors()->add($attribute, $message);

        throw new ValidationException($validator);
    }

    /**
     * Resolve the target enum case or raise the standard validation shape.
     *
     * A string current value is accepted only as runtime persistence input;
     * transition declarations themselves remain enum-case based.
     *
     * @param  TState|string  $current
     * @return TState
     *
     * @throws ValidationException
     */
    public function resolve(
        BackedEnum|string $current,
        string $attribute = 'state',
        ?string $message = null,
    ): BackedEnum {
        if (! $this->to instanceof BackedEnum) {
            throw new LogicException('A guard-only enum transition has no target state to resolve.');
        }

        $this->assertAllowed($current, $attribute, $message);

        return $this->to;
    }
}
