<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Nexia\Approval\Resolver\ResolverContext;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/**
 * Names the person a record is *about*, so an app-neutral approval-line
 * resolver can anchor a relative line on that person instead of the submitter.
 *
 * Approval submissions routinely separate who files a request from who the
 * request concerns: a recruiter drafts an offer that the requesting department
 * decides, an HR administrator files onboarding for an incoming worker. A
 * submitter-relative route climbs the filer's own reporting line and asks the
 * wrong chain.
 *
 * Only the owning App can answer this question — the subject is a domain fact
 * ("the requisition's owner", "the worker taking leave"), not something Core or
 * another App may infer. The App implements this contract and registers it per
 * resource key; every resolver then reads {@see ResolverContext::$subjectActor}
 * without naming the App.
 *
 * Return `null` when the subject cannot be determined. A resolver anchored on a
 * missing subject fails closed rather than silently falling back to the
 * submitter, which would approve the wrong line without saying so.
 */
interface ResourceSubjectActor
{
    /**
     * The subject's host User key for this record, or `null` when unresolvable.
     *
     * Resolve inside `$legalEntity` only: a record owned elsewhere must not
     * lend its subject to this Legal Entity's approval line.
     */
    public function subjectActorKey(ResourceRef $resourceRef, LegalEntity $legalEntity): ?int;
}
