<?php

declare(strict_types=1);

namespace Nexia\Attachments\Contracts;

use Closure;
use Nexia\Attachments\AuthorizedImportFile;
use Nexia\Identity\Contracts\Actor;

/**
 * Claims a clean import upload and lets an App process a Core-managed local copy.
 *
 * The copy is streamed by the host and deleted after the callback. Apps receive
 * neither the host storage path nor the complete file contents in memory.
 */
interface AuthorizedImportFileStore
{
    /** Discard an unused claim after the App has ruled out domain references. */
    public function discardUnbound(string $filePublicId, string $resourceKey, int|string $legalEntityKey, Actor $actor): bool;

    /**
     * Receive a legacy server-local multipart temporary file through the same
     * guarded upload-intent, scan, and claim path as a client upload.
     */
    public function receiveLocal(
        string $path,
        string $originalName,
        string $resourceKey,
        int|string $legalEntityKey,
        Actor $actor,
    ): ?AuthorizedImportFile;

    public function claim(
        string $uploadIntentPublicId,
        string $resourceKey,
        int|string $legalEntityKey,
        Actor $actor,
    ): ?AuthorizedImportFile;

    /**
     * Re-authorize a previously claimed file without transferring its bytes.
     *
     * Apps use this to address a durable parsed snapshot. Reading the original
     * content still requires {@see withLocalCopy()}.
     */
    public function find(
        string $filePublicId,
        string $resourceKey,
        int|string $legalEntityKey,
        Actor $actor,
    ): ?AuthorizedImportFile;

    /** @template T @param Closure(string, AuthorizedImportFile): T $callback @return T|null */
    public function withLocalCopy(
        string $filePublicId,
        string $resourceKey,
        int|string $legalEntityKey,
        Actor $actor,
        Closure $callback,
    ): mixed;
}
