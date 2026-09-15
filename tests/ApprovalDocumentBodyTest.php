<?php

declare(strict_types=1);

use Nexia\Approval\Domain\Form\DocumentBody;
use Nexia\Approval\Domain\Form\FormSchemaException;
use Nexia\Approval\Domain\Form\SubmittedFormSnapshot;

require dirname(__DIR__).'/vendor/autoload.php';

$sanitized = DocumentBody::fromHtml(
    '<h2 style="text-align:center" onclick="alert(1)">Title</h2>'.
    '<script>alert(1)</script>'.
    '<a href="javascript:alert(1)">unsafe</a>'.
    '<a href="https://example.com" target="_blank">safe</a>'.
    '<img src="data:image/png;base64,AA==" onerror="alert(1)">',
)->assertNotEmpty()->html();

foreach (['<script', 'onclick=', 'javascript:', 'onerror='] as $unsafe) {
    if (str_contains(strtolower($sanitized), $unsafe)) {
        throw new RuntimeException("Approval document sanitizer retained [{$unsafe}].");
    }
}

foreach (['<h2', 'text-align:center', 'https://example.com', 'data:image/png;base64,AA'] as $safe) {
    if (! str_contains($sanitized, $safe)) {
        throw new RuntimeException("Approval document sanitizer removed expected content [{$safe}].");
    }
}

foreach (['', '   ', '<p> </p>', '<p>&nbsp;</p>', '<script>alert(1)</script>'] as $empty) {
    try {
        DocumentBody::fromHtml($empty)->assertNotEmpty();
    } catch (FormSchemaException $exception) {
        if ($exception->toErrorPayload() !== [
            'code' => 'form_content_required',
            'params' => [],
        ]) {
            throw new RuntimeException('Approval form error payload changed unexpectedly.');
        }

        continue;
    }

    throw new RuntimeException('Sanitized-empty approval body was accepted.');
}

$expected = [
    'template_key' => 'expense-report',
    'template_version' => 3,
    'body' => $sanitized,
];

if (SubmittedFormSnapshot::fromArray($expected)->toArray() !== $expected) {
    throw new RuntimeException('Submitted approval form snapshot changed unexpectedly.');
}

fwrite(STDOUT, "Approval document body contracts passed.\n");
