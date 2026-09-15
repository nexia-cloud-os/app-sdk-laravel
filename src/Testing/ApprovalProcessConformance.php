<?php

declare(strict_types=1);

namespace Nexia\Testing;

use LogicException;
use Nexia\AppDescriptors\ProcessWorkActionDescriptor;

/** Reusable App test fixture for an SDK-owned BPMN Approval Task contract. */
final class ApprovalProcessConformance
{
    private function __construct() {}

    /**
     * @param  array<string, mixed>  $structure
     */
    public static function assert(
        ProcessWorkActionDescriptor $descriptor,
        array $structure,
        string $elementId,
    ): void {
        $violations = self::violations($descriptor, $structure, $elementId);
        if ($violations !== []) {
            throw new LogicException(
                "Approval Process conformance failed:\n- ".implode("\n- ", $violations),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $structure
     * @return list<string>
     */
    public static function violations(
        ProcessWorkActionDescriptor $descriptor,
        array $structure,
        string $elementId,
    ): array {
        $violations = [];
        foreach (self::duplicateElementIds($structure) as $duplicateId) {
            $violations[] = "The BPMN structure declares duplicate element id [{$duplicateId}].";
        }
        $approval = $descriptor->approvalTask;
        if ($approval === null) {
            return ['The work-action descriptor does not declare Approval Task semantics.'];
        }
        if ($descriptor->kind !== 'serviceTask') {
            $violations[] = 'An Approval Task must be a serviceTask.';
        }

        $outcome = $descriptor->outputContract['approval_outcome'] ?? null;
        if (! is_array($outcome) || ($outcome['type'] ?? null) !== 'string') {
            $violations[] = 'The descriptor must declare string output [approval_outcome].';
        } else {
            $declaredOutcomes = is_array($outcome['enum'] ?? null)
                ? array_values($outcome['enum'])
                : [];
            if ($declaredOutcomes !== $approval->outcomes) {
                $violations[] = 'The approval_outcome enum must exactly match Approval Task outcomes.';
            }
            if (! is_string($outcome['label_key'] ?? null)
                || trim($outcome['label_key']) === '') {
                $violations[] = 'The approval_outcome output must declare label_key.';
            }
            $enumLabels = is_array($outcome['enum_labels'] ?? null) ? $outcome['enum_labels'] : [];
            foreach ($approval->outcomes as $value) {
                if (! is_string($enumLabels[$value] ?? null) || trim($enumLabels[$value]) === '') {
                    $violations[] = "The approval_outcome enum value [{$value}] needs an i18n label.";
                }
            }
        }

        $elements = self::elements($structure);
        $node = $elements[$elementId] ?? null;
        if (! is_array($node)) {
            return [...$violations, "Approval Task element [{$elementId}] was not found."];
        }
        if (($node['type'] ?? null) !== 'serviceTask') {
            $violations[] = "Approval Task element [{$elementId}] must be a serviceTask.";
        }
        $binding = is_array($node['nexia:workAction'] ?? null)
            ? $node['nexia:workAction']
            : (is_array($node['extensionElements']['nexia']['workAction'] ?? null)
                ? $node['extensionElements']['nexia']['workAction']
                : []);
        if (($binding['app'] ?? null) !== $descriptor->appKey
            || ($binding['action_key'] ?? null) !== $descriptor->actionKey) {
            $violations[] = 'The BPMN task work-action binding does not match the descriptor.';
        }
        $topic = $node['nexia:topic'] ?? $node['topic'] ?? null;
        if ($topic !== $descriptor->topic) {
            $violations[] = 'The BPMN task topic does not match the descriptor.';
        }

        $inputAssociations = is_array($node['dataInputAssociations'] ?? null)
            ? $node['dataInputAssociations']
            : [];
        $outputAssociations = is_array($node['dataOutputAssociations'] ?? null)
            ? $node['dataOutputAssociations']
            : [];
        if ($inputAssociations === []) {
            $violations[] = 'The Approval Task must exercise at least one dataInputAssociation.';
        }
        if ($outputAssociations === []) {
            $violations[] = 'The Approval Task must exercise dataOutputAssociations.';
        }

        $outputIdByName = [];
        foreach (($node['ioSpecification']['dataOutputs'] ?? []) as $dataOutput) {
            if (is_array($dataOutput)
                && is_string($dataOutput['id'] ?? null)
                && is_string($dataOutput['name'] ?? null)) {
                $outputIdByName[$dataOutput['name']] = $dataOutput['id'];
            }
        }
        $outcomeOutputId = $outputIdByName['approval_outcome'] ?? null;
        $outcomeTargetPath = null;
        foreach ($outputAssociations as $association) {
            if (! is_array($association)) {
                continue;
            }
            $sourceRefs = is_array($association['sourceRefs'] ?? null)
                ? $association['sourceRefs']
                : [];
            $nexia = is_array($association['extensionElements']['nexia'] ?? null)
                ? $association['extensionElements']['nexia']
                : [];
            if ($outcomeOutputId !== null
                && in_array($outcomeOutputId, $sourceRefs, true)
                && ($nexia['sourcePath'] ?? null) === 'approval_outcome'
                && is_string($nexia['targetPath'] ?? null)
                && str_starts_with($nexia['targetPath'], 'variables.')) {
                $outcomeTargetPath = substr($nexia['targetPath'], strlen('variables.'));
                break;
            }
        }
        if ($outcomeTargetPath === null || $outcomeTargetPath === '') {
            $violations[] = 'approval_outcome must leave the task through an output association into variables.*.';
        } else {
            self::assertOutcomeGateway(
                $violations,
                $elements,
                $elementId,
                $outcomeTargetPath,
                $approval->outcomes,
            );
        }

        return array_values(array_unique($violations));
    }

    /**
     * @param  list<string>  $violations
     * @param  array<string, array<string, mixed>>  $elements
     * @param  list<string>  $outcomes
     */
    private static function assertOutcomeGateway(
        array &$violations,
        array $elements,
        string $elementId,
        string $outcomeTargetPath,
        array $outcomes,
    ): void {
        $incoming = array_values(array_filter(
            $elements,
            static fn (array $element): bool => ($element['type'] ?? null) === 'sequenceFlow'
                && ($element['source'] ?? null) === $elementId,
        ));
        $gatewayId = count($incoming) === 1 ? ($incoming[0]['target'] ?? null) : null;
        $gateway = is_string($gatewayId) ? ($elements[$gatewayId] ?? null) : null;
        if (! is_array($gateway) || ($gateway['type'] ?? null) !== 'exclusiveGateway') {
            $violations[] = 'The Approval Task must flow directly into an exclusive outcome Gateway.';

            return;
        }

        $outgoing = array_values(array_filter(
            $elements,
            static fn (array $element): bool => ($element['type'] ?? null) === 'sequenceFlow'
                && ($element['source'] ?? null) === $gatewayId,
        ));
        $hasDefault = false;
        $conditionValues = [];
        foreach ($outgoing as $flow) {
            if (($flow['default'] ?? false) === true) {
                $hasDefault = true;
            }
            $condition = is_array($flow['condition'] ?? null) ? $flow['condition'] : null;
            if ($condition === null) {
                continue;
            }
            if (($condition['variable'] ?? null) !== $outcomeTargetPath) {
                $violations[] = 'The Approval Gateway condition must read the mapped approval_outcome variable.';
            }
            if (is_string($condition['value'] ?? null)) {
                $conditionValues[] = $condition['value'];
            }
        }
        if (! $hasDefault) {
            $violations[] = 'The Approval Gateway must declare a default path for non-approved outcomes.';
        }
        if (! in_array('approved', $conditionValues, true)) {
            $violations[] = 'The Approval Gateway must declare an explicit approved path.';
        }
        foreach ($conditionValues as $value) {
            if (! in_array($value, $outcomes, true)) {
                $violations[] = "The Approval Gateway condition [{$value}] is not a declared outcome.";
            }
        }
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

    /** @param array<string, mixed> $structure @return list<string> */
    private static function duplicateElementIds(array $structure): array
    {
        $counts = [];
        $visit = static function (mixed $value) use (&$visit, &$counts): void {
            if (! is_array($value)) {
                return;
            }
            if (is_string($value['id'] ?? null) && is_string($value['type'] ?? null)) {
                $counts[$value['id']] = ($counts[$value['id']] ?? 0) + 1;
            }
            foreach ($value as $child) {
                if (is_array($child)) {
                    $visit($child);
                }
            }
        };
        $visit($structure);

        return array_keys(array_filter($counts, static fn (int $count): bool => $count > 1));
    }
}
