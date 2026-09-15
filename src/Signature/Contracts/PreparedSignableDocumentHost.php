<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Identity\Contracts\Actor;
use Nexia\Signature\PreparedSignableDocumentResult;
use Nexia\Signature\PreparedSignableDocumentSubmission;

/** App-neutral bridge for turning one uploaded PDF into one immutable signable document. */
interface PreparedSignableDocumentHost
{
    /**
     * Idempotently advances upload normalization, participant/field freezing,
     * and SignableDocument materialization as far as the host can currently go.
     */
    public function prepare(PreparedSignableDocumentSubmission $submission): PreparedSignableDocumentResult;

    /** Returns the latest safe projection only to the preparation owner. */
    public function result(string $preparedDocumentPublicId, Actor $actor): ?PreparedSignableDocumentResult;
}
