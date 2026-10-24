<?php

declare(strict_types=1);

namespace Nexia\DocumentData;

/** Shared value contract; the legacy Signature name remains supported. */
class_alias(\Nexia\Signature\SignatureDocumentDataFormatter::class, __NAMESPACE__.'\\DocumentDataFormatter');
