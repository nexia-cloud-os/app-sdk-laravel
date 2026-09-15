<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignableDocumentStatus: string
{
    case Queued = 'queued';
    case Rendering = 'rendering';
    case Ready = 'ready';
    case RetryableFailed = 'retryable_failed';
    case TerminalFailed = 'terminal_failed';
    case Cancelled = 'cancelled';
    case Superseded = 'superseded';
}
