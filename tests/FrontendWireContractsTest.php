<?php

declare(strict_types=1);

use Nexia\AppRuntime\AppAvailability;
use Nexia\Events\HandoffResultState;
use Nexia\ResourceReference\ReferenceStatus;
use Nexia\Signature\SignatureDataClassification;
use Nexia\Signature\SignatureDocumentDataFieldType;
use Nexia\Signature\SignatureDocumentDataFormatter;
use Nexia\Signature\SignatureDocumentDataLookupMode;
use Nexia\Signature\SignatureDocumentDataStatus;

require dirname(__DIR__).'/vendor/autoload.php';

/** @return list<array{id: string, php_enum: class-string<UnitEnum>, values: list<string>}> */
function wireContracts(): array
{
    $fixture = json_decode(
        (string) file_get_contents(__DIR__.'/Fixtures/wire-values.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (($fixture['schema_version'] ?? null) !== 1 || ! is_array($fixture['contracts'] ?? null)) {
        throw new RuntimeException('Invalid wire-values fixture.');
    }

    return $fixture['contracts'];
}

/** @param class-string<UnitEnum> $enum */
function enumWireValues(string $enum): array
{
    return array_map(static fn (BackedEnum $case): string => $case->value, $enum::cases());
}

foreach (wireContracts() as $contract) {
    if (enumWireValues($contract['php_enum']) !== $contract['values']) {
        throw new RuntimeException("PHP wire values differ for [{$contract['id']}].");
    }
}

fwrite(STDOUT, "Laravel wire contracts passed.\n");
