<?php

declare(strict_types=1);

namespace Nexia\Identity\Query;

use RuntimeException;

/** Internal signal: the selected combination has no equivalent host operation. */
final class UnsupportedIdentityQuery extends RuntimeException {}
