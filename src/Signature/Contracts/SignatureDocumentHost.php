<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Identity\Contracts\Actor;
use Nexia\Signature\CurrentBoundSignableDocumentResult;
use Nexia\Signature\CurrentBoundSignableDocumentSubmission;
use Nexia\Signature\PreReadySignableDocumentReference;
use Nexia\Signature\SignableDocumentReference;
use Nexia\Signature\SignableDocumentSummary;

/** App-neutral document capability, deliberately separate from signature requests. */
interface SignatureDocumentHost
{
    public function createCurrentBound(
        CurrentBoundSignableDocumentSubmission $submission,
    ): CurrentBoundSignableDocumentResult;

    public function replace(
        SignableDocumentReference $predecessor,
        CurrentBoundSignableDocumentSubmission $submission,
    ): CurrentBoundSignableDocumentResult;

    /**
     * Cancels a queued/rendering revision and creates exactly one new queued
     * successor from the current template winner. This is deliberately not a
     * create retry and cannot be used after a source has become ready.
     */
    public function rebuildPreReady(
        PreReadySignableDocumentReference $predecessor,
        CurrentBoundSignableDocumentSubmission $submission,
    ): CurrentBoundSignableDocumentResult;

    /** Requeues the exact retryable or attempts-exhausted revision after its owning workflow re-authorizes it. */
    public function retryPreReady(
        PreReadySignableDocumentReference $document,
        Actor $actor,
        string $reasonCode,
    ): CurrentBoundSignableDocumentResult;

    public function summary(string $publicId, Actor $actor): ?SignableDocumentSummary;

    /**
     * Returns a protected inline-preview URL only when the actor currently
     * holds both document-content and bound-resource authority.
     */
    public function previewHrefIfAuthorized(string $publicId, Actor $actor): ?string;

    /**
     * Returns a protected source-download URL only when the actor currently
     * holds document-content, download-source, and bound-resource authority.
     */
    public function sourceDownloadHrefIfAuthorized(string $publicId, Actor $actor): ?string;

    public function cancel(string $publicId, Actor $actor): SignableDocumentSummary;
}
