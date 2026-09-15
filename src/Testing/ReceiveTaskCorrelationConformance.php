<?php

declare(strict_types=1);

namespace Nexia\Testing;

use LogicException;
use Nexia\AppDescriptors\ProcessWorkActionDescriptor;
use Nexia\Process\Contracts\ProcessRuntime;
use Nexia\Process\Domain\Enums\ProcessMessageDeliveryStatus;
use Nexia\Process\ProcessInstanceSnapshot;
use Nexia\Process\ProcessMessageDelivery;
use Nexia\Process\ProcessMessageDeliveryResult;
use Nexia\Process\ReceiveTaskCorrelation;
use Throwable;

/** Proves that a receiveTask has both a valid BPMN mapping and a real App correlation path. */
final class ReceiveTaskCorrelationConformance
{
    private function __construct() {}

    /**
     * @param  array<string, mixed>  $structure
     * @param  list<string>  $requiredPayloadKeys
     * @param  callable(ProcessRuntime): void  $runAppCorrelation
     */
    public static function assert(
        ProcessWorkActionDescriptor $descriptor,
        array $structure,
        string $elementId,
        array $requiredPayloadKeys,
        callable $runAppCorrelation,
    ): void {
        $violations = self::violations($descriptor, $structure, $elementId, $requiredPayloadKeys, $runAppCorrelation);
        if ($violations !== []) {
            throw new LogicException("Receive Task correlation conformance failed:\n- ".implode("\n- ", $violations));
        }
    }

    /**
     * @param  array<string, mixed>  $structure
     * @param  list<string>  $requiredPayloadKeys
     * @param  callable(ProcessRuntime): void  $runAppCorrelation
     * @return list<string>
     */
    public static function violations(
        ProcessWorkActionDescriptor $descriptor,
        array $structure,
        string $elementId,
        array $requiredPayloadKeys,
        callable $runAppCorrelation,
    ): array {
        $violations = [];
        if ($descriptor->kind !== 'receiveTask') {
            $violations[] = 'The descriptor must declare kind [receiveTask].';
        }

        $elements = self::elements($structure);
        $node = $elements[$elementId] ?? null;
        if (! is_array($node) || ($node['type'] ?? null) !== 'receiveTask') {
            return [...$violations, "Receive Task element [{$elementId}] was not found."];
        }
        if (($node['nexia:topic'] ?? $node['topic'] ?? null) !== $descriptor->topic) {
            $violations[] = 'The receiveTask topic does not match the descriptor.';
        }
        $binding = $node['nexia:workAction'] ?? $node['extensionElements']['nexia']['workAction'] ?? [];
        if (! is_array($binding)
            || ($binding['app'] ?? null) !== $descriptor->appKey
            || ($binding['action_key'] ?? null) !== $descriptor->actionKey) {
            $violations[] = 'The receiveTask work-action binding does not match the descriptor.';
        }

        $inputIds = [];
        foreach (($node['ioSpecification']['dataInputs'] ?? []) as $input) {
            if (is_array($input) && is_string($input['id'] ?? null) && is_string($input['name'] ?? null)) {
                $inputIds[$input['name']] = $input['id'];
            }
        }
        foreach ($requiredPayloadKeys as $key) {
            $inputId = $inputIds[$key] ?? null;
            $mapped = false;
            foreach (($node['dataInputAssociations'] ?? []) as $association) {
                $nexia = is_array($association['extensionElements']['nexia'] ?? null)
                    ? $association['extensionElements']['nexia']
                    : [];
                if (is_string($inputId)
                    && ($association['targetRef'] ?? null) === $inputId
                    && ($nexia['sourcePath'] ?? null) === $key
                    && is_string($nexia['targetPath'] ?? null)
                    && str_starts_with($nexia['targetPath'], 'variables.')) {
                    $mapped = true;
                    break;
                }
            }
            if (! $mapped) {
                $violations[] = "Incoming message key [{$key}] is not mapped into a process variable.";
            }
        }

        $runtime = new class implements ProcessRuntime
        {
            public ?ProcessMessageDelivery $delivery = null;

            public function hasPublishedEventStart(int $legalEntityKey, string $eventName): bool
            {
                return true;
            }

            public function findInstance(string $publicId): ?ProcessInstanceSnapshot
            {
                return null;
            }

            public function correlateReceiveTask(ReceiveTaskCorrelation $correlation): bool
            {
                return true;
            }

            public function deliverMessage(ProcessMessageDelivery $delivery): ProcessMessageDeliveryResult
            {
                $this->delivery = $delivery;

                return new ProcessMessageDeliveryResult(ProcessMessageDeliveryStatus::Consumed);
            }
        };
        try {
            $runAppCorrelation($runtime);
        } catch (Throwable $exception) {
            $violations[] = sprintf('The App correlation path threw %s.', $exception::class);
        }

        if (! $runtime->delivery instanceof ProcessMessageDelivery) {
            $violations[] = 'The App correlation path never called ProcessRuntime::deliverMessage().';
        } else {
            if ($runtime->delivery->elementId !== $elementId || $runtime->delivery->topic !== $descriptor->topic) {
                $violations[] = 'The App delivered to a different receiveTask element or topic.';
            }
            foreach ($requiredPayloadKeys as $key) {
                if (! array_key_exists($key, $runtime->delivery->payload)
                    || $runtime->delivery->payload[$key] === null
                    || $runtime->delivery->payload[$key] === '') {
                    $violations[] = "The App delivery omitted required message key [{$key}].";
                }
            }
        }

        return array_values(array_unique($violations));
    }

    /** @param array<string, mixed> $structure @return array<string, array<string, mixed>> */
    private static function elements(array $structure): array
    {
        $result = [];
        $visit = static function (mixed $value) use (&$visit, &$result): void {
            if (! is_array($value)) {
                return;
            }
            if (is_string($value['id'] ?? null) && is_string($value['type'] ?? null)) {
                $result[$value['id']] = $value;
            }
            foreach ($value as $child) {
                if (is_array($child)) {
                    $visit($child);
                }
            }
        };
        $visit($structure);

        return $result;
    }
}
