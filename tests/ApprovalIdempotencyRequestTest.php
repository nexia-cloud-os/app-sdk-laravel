<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Nexia\Laravel\Approval\ApprovalIdempotencyRequest;

require dirname(__DIR__).'/vendor/autoload.php';

$request = Request::create('/', 'POST', [], [], [], [
    'HTTP_IDEMPOTENCY_KEY' => '  approval-request-key  ',
]);

if (ApprovalIdempotencyRequest::requireKey($request, 'missing') !== 'approval-request-key') {
    throw new RuntimeException('Approval idempotency request normalization changed unexpectedly.');
}

fwrite(STDOUT, "Approval idempotency HTTP adapter contracts passed.\n");
