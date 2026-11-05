<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Contracts;

/**
 * Explicitly opts a tenant-owned report filter into dashboard Resource queries.
 *
 * The existing report filter must have the same legal-entity applicability for
 * dashboard reads, including multiple selected entities and the supplied actor.
 * The host validates the selection and independently enforces Resource and row
 * permissions; this declaration never grants access or changes Agent reads.
 * Personal widgets do not require this declaration.
 */
interface DashboardReportLegalEntityFilter extends ReportLegalEntityFilter {}
