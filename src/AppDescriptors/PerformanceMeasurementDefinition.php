<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;

/** Semantic catalog metadata for one numeric performance measurement. */
final readonly class PerformanceMeasurementDefinition
{
    public const string SUBJECT_RESOURCE_KEY = 'directory.party';

    public const string SOURCE_LEGAL_ENTITY_RESOURCE_KEY = 'enterprise.legal_entity';

    public function __construct(
        public string $key,
        public string $metricVersion,
        public string $labelKey,
        public string $unitCode,
        public PerformanceMeasurementValueType $valueType,
        public PerformanceMeasurementTimeSemantics $timeSemantics,
        public string $subjectResourceKey = self::SUBJECT_RESOURCE_KEY,
        public string $sourceLegalEntityResourceKey = self::SOURCE_LEGAL_ENTITY_RESOURCE_KEY,
    ) {
        if (mb_strlen($key) > 160 || preg_match('/\A[a-z][a-z0-9_]*\z/D', $key) !== 1) {
            throw new InvalidArgumentException('Performance measurement keys must be canonical and at most 160 characters.');
        }
        if ($metricVersion === ''
            || $metricVersion !== trim($metricVersion)
            || mb_strlen($metricVersion) > 64) {
            throw new InvalidArgumentException('Performance measurement versions must be normalized and at most 64 characters.');
        }
        if ($labelKey === '' || $labelKey !== trim($labelKey) || mb_strlen($labelKey) > 160) {
            throw new InvalidArgumentException('Performance measurement label keys must be normalized and at most 160 characters.');
        }
        if (mb_strlen($unitCode) > 64
            || preg_match('/\A[A-Za-z0-9%._\/-]+\z/D', $unitCode) !== 1) {
            throw new InvalidArgumentException('Performance measurement unit codes must be canonical and at most 64 characters.');
        }
        if ($subjectResourceKey !== self::SUBJECT_RESOURCE_KEY
            || $sourceLegalEntityResourceKey !== self::SOURCE_LEGAL_ENTITY_RESOURCE_KEY) {
            throw new InvalidArgumentException('Performance measurements require Party subjects and source Legal Entity context.');
        }
    }
}
