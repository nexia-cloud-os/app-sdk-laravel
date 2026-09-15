<?php

declare(strict_types=1);

namespace Nexia\Testing;

use LogicException;
use Nexia\AppDescriptors\ProcessUserTaskFormDescriptor;
use Nexia\Process\Contracts\ProcessUserTaskSubmissionRegistrar;

/** Pairs every slot-backed UserTask form declaration with its App handler. */
final class ProcessUserTaskSubmissionConformance
{
    private function __construct() {}

    /**
     * @param  list<ProcessUserTaskFormDescriptor>  $descriptors
     *
     * @throws LogicException
     */
    public static function assert(
        string $appKey,
        array $descriptors,
        ProcessUserTaskSubmissionRegistrar $registrar,
    ): void {
        $violations = self::violations($appKey, $descriptors, $registrar);
        if ($violations !== []) {
            throw new LogicException(
                "Process UserTask submission conformance failed:\n- ".implode("\n- ", $violations),
            );
        }
    }

    /**
     * @param  list<ProcessUserTaskFormDescriptor>  $descriptors
     * @return list<string>
     */
    public static function violations(
        string $appKey,
        array $descriptors,
        ProcessUserTaskSubmissionRegistrar $registrar,
    ): array {
        $violations = [];

        foreach ($descriptors as $descriptor) {
            if ($descriptor->appKey !== $appKey
                || ($descriptor->rendering['mode'] ?? 'core_schema') !== 'slot_widget') {
                continue;
            }

            $actionKey = $descriptor->submissionActionKey;
            if (! is_string($actionKey) || trim($actionKey) === '') {
                $violations[] = "slot_widget form [{$descriptor->key}] declares no submission action.";

                continue;
            }

            if (! $registrar->registered($descriptor->appKey, $actionKey)) {
                $violations[] = sprintf(
                    'slot_widget form [%s] declares submission [%s.%s], but no handler is registered. '
                    .'Register it through ProcessUserTaskSubmissionRegistrar or stop contributing the form.',
                    $descriptor->key,
                    $descriptor->appKey,
                    $actionKey,
                );
            }
        }

        return $violations;
    }
}
