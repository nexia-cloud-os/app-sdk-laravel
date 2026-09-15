<?php

declare(strict_types=1);

namespace Nexia\Documents;

use RuntimeException;

/** A host-local PDF parser could not establish structural facts safely. */
final class PdfInspectionException extends RuntimeException {}
