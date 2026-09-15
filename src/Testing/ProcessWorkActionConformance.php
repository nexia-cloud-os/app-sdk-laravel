<?php

declare(strict_types=1);

namespace Nexia\Testing;

use LogicException;
use Nexia\AppDescriptors\ProcessWorkActionDescriptor;
use Nexia\Process\Contracts\ProcessWorkActionRegistrar;

/**
 * Reusable App test fixture pairing catalogued work actions with registered
 * handlers.
 *
 * A `ProcessWorkActionDescriptor` only names an action; the handler that runs
 * it is registered separately at app boot. Nothing connects the two: a template
 * may declare the action as a `worker` dependency, the descriptor validates,
 * and the definition publishes — the gap only appears when an instance reaches
 * the step, the external task finds no handler, and the token stops with an
 * incident nobody expected. Two contributions in this repository already shipped
 * that way.
 *
 * Only `serviceTask` is checked. A `receiveTask` action waits for a correlated
 * inbound message and has no worker handler by design.
 *
 * Usage — from the App's contract test, after the app has booted:
 *
 *   ProcessWorkActionConformance::assert('sample-workflow', $descriptors, $registrar);
 */
final class ProcessWorkActionConformance
{
    private function __construct() {}

    /**
     * @param  list<ProcessWorkActionDescriptor>  $descriptors  every action the App contributes
     *
     * @throws LogicException when a serviceTask action has no registered handler
     */
    public static function assert(
        string $appKey,
        array $descriptors,
        ProcessWorkActionRegistrar $registrar,
    ): void {
        $violations = self::violations($appKey, $descriptors, $registrar);
        if ($violations !== []) {
            throw new LogicException(
                "Process work-action conformance failed:\n- ".implode("\n- ", $violations),
            );
        }
    }

    /**
     * @param  list<ProcessWorkActionDescriptor>  $descriptors
     * @return list<string>
     */
    public static function violations(
        string $appKey,
        array $descriptors,
        ProcessWorkActionRegistrar $registrar,
    ): array {
        $violations = [];

        foreach ($descriptors as $descriptor) {
            if ($descriptor->appKey !== $appKey || $descriptor->kind !== 'serviceTask') {
                continue;
            }

            if (! $registrar->registered($descriptor->appKey, $descriptor->actionKey)) {
                $violations[] = sprintf(
                    'serviceTask [%s.%s] is catalogued but no handler is registered, so an instance '
                    .'reaching this step fails its external task. Register it through '
                    .'ProcessWorkActionRegistrar, or stop contributing the descriptor.',
                    $descriptor->appKey,
                    $descriptor->actionKey,
                );
            }
        }

        return $violations;
    }
}
