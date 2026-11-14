<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use DateTimeImmutable;
use InvalidArgumentException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Shared query selector validation for host and runtime implementations. */
trait ResourceReferenceQuerySupport
{
    public function query(string $resourceKey, ResourceReferenceResolutionContext $context): ResourceReferenceQuery
    {
        if (func_num_args() !== 2) {
            throw new InvalidArgumentException('Reference query accepts a resource key and context only.');
        }

        return new ResourceReferenceQuery($this, $resourceKey, $context, $this->runReferenceQuery(...));
    }

    public function queryBatch(array $requests): ResourceReferenceQueryBatch
    {
        if (func_num_args() !== 1) {
            throw new InvalidArgumentException('Reference query batch accepts one query list only.');
        }

        return new ResourceReferenceQueryBatch($this, $requests, $this->runReferenceQueryBatch(...));
    }

    private function runReferenceQuery(string $resourceKey, ResourceReferenceResolutionContext $context, array $options): array
    {
        try {
            $legacyContextFilters = ($options['_allow_legacy_context_filters'] ?? false) === true;
            $strictSchema = ($options['_query_schema'] ?? false) === true
                || (! $legacyContextFilters && ($context->filters !== [] || $context->sort !== null));
            unset($options['_query_schema'], $options['_allow_legacy_context_filters']);
            if (trim($resourceKey) === '' || trim($resourceKey) !== $resourceKey || mb_strlen($resourceKey) > 160) {
                throw new InvalidArgumentException('A bounded resource key is required.');
            }

            if ($context->includeProtectedValues && (array_diff(array_keys($options), ['id', 'ids']) !== []
                || array_key_exists('id', $options) === array_key_exists('ids', $options))) {
                throw new ReferenceQueryFailure('PROTECTED_VALUES_UNAVAILABLE', null);
            }

            if ($strictSchema && $context->effectiveThrough !== null && ! $context->includeProtectedValues
                && (array_key_exists('id', $options) xor array_key_exists('ids', $options))
                && array_diff(array_keys($options), ['id', 'ids']) === []) {
                $ids = array_key_exists('id', $options) ? [$options['id']] : $options['ids'];
                if (! is_array($ids) || ! $this->validReferenceIds($ids)) {
                    throw new InvalidArgumentException('Invalid reference ID selector.');
                }
                $this->assertReferenceQuerySupported(array_key_exists('id', $options) ? 'resolve' : 'resolveMany');
                $this->assertReferenceQuerySupported('resolveManyGrouped');
                $groups = ['query-0' => ['resource_ids' => $ids, 'as_of' => $context->asOf,
                    'effective_through' => $context->effectiveThrough, 'filters' => $context->filters]];
                $grouped = $this->executeReferenceGroupedQuery($resourceKey, $groups, $context, true, false);

                return $this->referenceQuerySuccess($grouped['query-0'] ?? [], $options);
            }
            if ($strictSchema && (! array_key_exists('search', $options)
                || (! array_key_exists('page', $options) && ! array_key_exists('per_page', $options)))) {
                throw new ReferenceQueryFailure('UNSUPPORTED_IMPLEMENTATION', null);
            }

            return $this->referenceQuerySuccess($this->executeReferenceQuery($resourceKey, $context, $options, $strictSchema), $options);
        } catch (Exception $error) {
            return $this->referenceQueryException($error);
        }
    }

    private function executeReferenceQuery(string $resourceKey, ResourceReferenceResolutionContext $context, array $options, bool $strictSchema = false): ResolvedResourceReference|ResourceReferencePage|array|null
    {
        if (array_key_exists('complete', $options)) {
            if ($options['complete'] !== true || array_diff(array_keys($options), ['queries', 'from', 'until', 'complete']) !== []
                || ! is_array($options['queries'] ?? null)
                || $options['queries'] === [] || count($options['queries']) > 100
                || array_filter($options['queries'], static fn (mixed $term): bool => ! is_string($term) || $term === '' || mb_strlen($term) > 160) !== []
                || ! ($options['from'] ?? null) instanceof DateTimeImmutable
                || ! ($options['until'] ?? null) instanceof DateTimeImmutable || $options['from'] > $options['until']) {
                throw new InvalidArgumentException('Invalid complete effective-range query options.');
            }
            $this->assertReferenceQuerySupported('searchCompleteEffectiveRange');
            $result = $this->executeReferenceCompleteRangeQuery($resourceKey, array_values(array_unique($options['queries'])),
                $options['from'], $options['until'], $context);
            if ($result === null) {
                throw new ReferenceQueryFailure('COMPLETE_RANGE_UNAVAILABLE', null);
            }

            return $result;
        }
        if (array_key_exists('from', $options) || array_key_exists('until', $options) || array_key_exists('queries', $options)) {
            $list = array_key_exists('queries', $options);
            $allowed = $list ? ['queries', 'from', 'until', 'limit'] : ['from', 'until', 'page', 'per_page'];
            if (array_diff(array_keys($options), $allowed) !== []
                || ! ($options['from'] ?? null) instanceof DateTimeImmutable
                || ! ($options['until'] ?? null) instanceof DateTimeImmutable
                || ($list && (! is_array($options['queries'])
                    || array_filter($options['queries'], static fn ($term): bool => ! is_string($term)) !== []
                    || (array_key_exists('limit', $options) && ! is_int($options['limit']))))
                || (! $list && ((array_key_exists('page', $options) && ! is_int($options['page']))
                    || (array_key_exists('per_page', $options) && ! is_int($options['per_page']))))) {
                throw new InvalidArgumentException('Invalid effective-range query options.');
            }

            $this->assertReferenceQuerySupported($list ? 'searchEffectiveRange' : 'searchEffectiveRangePage');

            return $list
                ? $this->executeReferenceEffectiveRangeQuery($resourceKey, $options['queries'], $options['from'], $options['until'], $context, $options['limit'] ?? 10000, true)
                : $this->executeReferenceEffectiveRangePageQuery($resourceKey, $options['from'], $options['until'], $context, $options['page'] ?? 1, $options['per_page'] ?? 500, true);
        }
        if (array_key_exists('search', $options)) {
            $paged = array_key_exists('page', $options) || array_key_exists('per_page', $options);
            if (array_diff(array_keys($options), ['search', 'limit', 'page', 'per_page']) !== []
                || ! is_string($options['search'])
                || ($paged && (array_key_exists('limit', $options)
                    || (array_key_exists('page', $options) && ! is_int($options['page']))
                    || (array_key_exists('per_page', $options) && ! is_int($options['per_page']))))
                || (! $paged && array_key_exists('limit', $options) && ! is_int($options['limit']))) {
                throw new InvalidArgumentException('Invalid reference search options.');
            }

            $this->assertReferenceQuerySupported($paged ? 'searchPage' : 'search');

            return $this->executeReferenceSearchQuery($resourceKey, $context, $options['search'],
                $paged ? ($options['page'] ?? 1) : null, $paged ? ($options['per_page'] ?? 25) : null,
                $paged ? null : ($options['limit'] ?? 25), true, $strictSchema);
        }
        if (array_diff(array_keys($options), ['id', 'ids']) !== []
            || array_key_exists('id', $options) === array_key_exists('ids', $options)
            || (array_key_exists('id', $options)
                ? ! is_string($options['id'])
                : ! is_array($options['ids']) || ! $this->validReferenceIds($options['ids']))) {
            throw new InvalidArgumentException('Specify exactly one reference ID selector.');
        }

        $this->assertReferenceQuerySupported(array_key_exists('id', $options) ? 'resolve' : 'resolveMany');

        return $this->executeReferenceIdQuery($resourceKey, $context, $options['id'] ?? null, $options['ids'] ?? null, true);
    }

    private function runReferenceQueryBatch(array $requests, bool $sharedFailure = false): array
    {
        if (! array_is_list($requests)) {
            return $this->referenceQueryError('INVALID_BATCH');
        }
        $legacyContext = [];
        foreach ($requests as $index => &$item) {
            if (! is_array($item) || array_diff(array_keys($item), ['resource_key', 'context', 'options']) !== []
                || ! is_string($item['resource_key'] ?? null) || trim($item['resource_key']) === ''
                || trim($item['resource_key']) !== $item['resource_key'] || mb_strlen($item['resource_key']) > 160
                || ! ($item['context'] ?? null) instanceof ResourceReferenceResolutionContext
                || ! is_array($item['options'] ?? null)) {
                return $this->referenceQueryError('INVALID_BATCH');
            }
            $legacyContext[$index] = ($item['options']['_allow_legacy_context_filters'] ?? false) === true;
            unset($item['options']['_allow_legacy_context_filters']);
        }
        unset($item);

        $results = [];
        for ($i = 0, $count = count($requests); $i < $count;) {
            $item = $requests[$i];
            $context = $item['context'];
            $sameContext = [];
            if (! $context->includeProtectedValues && (($context->filters === [] && $context->sort === null) || $legacyContext[$i])
                && $this->referenceQuerySupports('resolveMany')) {
                for ($j = $i; $j < $count; $j++) {
                    $next = $requests[$j];
                    $scope = $next['context'];
                    $ids = $next['options']['ids'] ?? null;
                    if ($legacyContext[$j] !== $legacyContext[$i]
                        || $next['resource_key'] !== $item['resource_key'] || ! $this->sameBatchScope($context, $scope)
                        || $scope->includeProtectedValues || $scope->effectiveThrough != $context->effectiveThrough
                        || $scope->asOf != $context->asOf || $scope->filters !== $context->filters
                        || array_keys($next['options']) !== ['ids'] || ! is_array($ids) || ! $this->validReferenceIds($ids)
                        || ($sameContext !== [] && $ids !== $sameContext[0])) {
                        break;
                    }
                    $sameContext[] = $ids;
                }
            }
            if (count($sameContext) > 1) {
                try {
                    $ids = array_values(array_unique($sameContext[0]));
                    $values = $this->executeReferenceIdQuery($item['resource_key'], $context, null, $ids, true);
                    foreach ($sameContext as $selection) {
                        $results[] = $this->referenceQuerySuccess(array_intersect_key($values, array_flip($selection)), ['ids' => []]);
                    }
                } catch (Exception $error) {
                    foreach ($sameContext as $_) {
                        $results[] = $this->referenceQueryException($error);
                    }
                }
                $i += count($sameContext);
                continue;
            }
            $groups = [];
            if ($sharedFailure && ! $context->includeProtectedValues && $context->effectiveThrough !== null
                && $this->referenceQuerySupports('resolveMany') && $this->referenceQuerySupports('resolveManyGrouped')) {
                for ($j = $i; $j < $count; $j++) {
                    $next = $requests[$j];
                    $scope = $next['context'];
                    $ids = $next['options']['ids'] ?? null;
                    if ($legacyContext[$j] !== $legacyContext[$i]
                        || $next['resource_key'] !== $item['resource_key'] || ! $this->sameBatchScope($context, $scope)
                        || $scope->includeProtectedValues || $scope->effectiveThrough === null
                        || array_keys($next['options']) !== ['ids'] || ! is_array($ids) || ! $this->validReferenceIds($ids)) {
                        break;
                    }
                    $groups['query-'.$j] = ['resource_ids' => $ids, 'as_of' => $scope->asOf,
                        'effective_through' => $scope->effectiveThrough, 'filters' => $scope->filters];
                }
            }
            $strictSingleGroup = count($groups) === 1 && ! $legacyContext[$i]
                && ($context->filters !== [] || $context->sort !== null);
            if (count($groups) > 1 || $strictSingleGroup) {
                try {
                    $values = $this->executeReferenceGroupedQuery($item['resource_key'], $groups, $context, true, $legacyContext[$i]);
                    foreach ($groups as $key => $_) {
                        $results[] = $this->referenceQuerySuccess($values[$key] ?? [], ['ids' => []]);
                    }
                } catch (Exception $error) {
                    foreach ($groups as $_) {
                        $results[] = $this->referenceQueryException($error);
                    }
                }
                $i += count($groups);
                continue;
            }

            if ((($context->filters === [] && $context->sort === null) || $legacyContext[$i])
                && $this->referenceQuerySupports('resolveMany') && method_exists($this, 'supportsReferenceIdBatch')
                && $this->supportsReferenceIdBatch() && $this->canCombineReferenceSearches() && ! $context->includeProtectedValues) {
                $reads = [];
                for ($j = $i; $j < $count && count($reads) < 2; $j++) {
                    $next = $requests[$j];
                    $ids = $next['options']['ids'] ?? null;
                    if ($legacyContext[$j] !== $legacyContext[$i]
                        || ! $this->sameBatchScope($context, $next['context']) || $next['context']->includeProtectedValues
                        || ($next['context']->filters !== [] && ! $legacyContext[$j])
                        || array_keys($next['options']) !== ['ids'] || ! is_array($ids)
                        || $ids === [] || count($ids) > 100 || ! $this->validReferenceIds($ids)) {
                        break;
                    }
                    $reads[] = $next;
                }
                if (count($reads) > 1) {
                    try {
                        foreach ($this->executeReferenceBatchIds($reads) as $value) {
                            $results[] = $value['status'] === 200
                                ? $this->referenceQuerySuccess($value['result'], ['ids' => []])
                                : $this->referenceQueryError($value['code'] ?? $this->referenceQueryStatusCode($value['status']), $value['status']);
                        }
                    } catch (Exception $error) {
                        foreach ($reads as $_) {
                            $results[] = $this->referenceQueryException($error);
                        }
                    }
                    $i += count($reads);
                    continue;
                }
            }

            if ((($context->filters === [] && $context->sort === null) || $legacyContext[$i])
                && $this->referenceQuerySupports('search') && $this->referenceQuerySupports('searchBatch')
                && method_exists($this, 'canCombineReferenceSearches') && $this->canCombineReferenceSearches()
                && ! $context->includeProtectedValues && $this->isSimpleBatchSearch($item['options'])) {
                $searches = [];
                for ($j = $i; $j < $count && count($searches) < 2; $j++) {
                    $next = $requests[$j];
                    if ($legacyContext[$j] !== $legacyContext[$i]
                        || ! $this->sameBatchScope($context, $next['context']) || $next['context']->includeProtectedValues
                        || ($next['context']->filters !== [] && ! $legacyContext[$j])
                        || ! $this->isSimpleBatchSearch($next['options'])) {
                        break;
                    }
                    $searches[] = ['resource_key' => $next['resource_key'], 'query' => $next['options']['search'],
                        'context' => $next['context'], 'limit' => $next['options']['limit'] ?? 25];
                }
                if (count($searches) > 1) {
                    try {
                        foreach ($this->executeReferenceBatchSearch($searches) as $value) {
                            $results[] = $value['status'] === 200
                                ? $this->referenceQuerySuccess($value['result'], ['search' => ''])
                                : $this->referenceQueryError($this->referenceQueryStatusCode($value['status']), $value['status']);
                        }
                    } catch (Exception $error) {
                        foreach ($searches as $_) {
                            $results[] = $this->referenceQueryException($error);
                        }
                    }
                    $i += count($searches);
                    continue;
                }
            }

            try {
                $options = $item['options'];
                if ($legacyContext[$i]) {
                    $options['_allow_legacy_context_filters'] = true;
                }
                $results[] = $this->runReferenceQuery($item['resource_key'], $context, $options);
            } catch (Exception $error) {
                $results[] = $this->referenceQueryException($error);
            }
            $i++;
        }

        $successful = count(array_filter($results, static fn (array $result): bool => $result['status'] === 'success'));

        return [
            'status' => $successful === count($results) ? 'success' : ($successful === 0 ? 'error' : 'partial'),
            'data' => $results,
            'page' => null,
            'error' => null,
        ];
    }

    private function referenceQuerySuccess(ResolvedResourceReference|ResourceReferencePage|array|null $value, array $options): array
    {
        $paged = array_key_exists('page', $options) || array_key_exists('per_page', $options)
            || (array_key_exists('from', $options) && ! array_key_exists('queries', $options));
        if ($value === null && $paged) {
            // A null page can mean unsupported or unavailable; the old contract cannot distinguish them.
            return $this->referenceQueryError('PAGE_UNAVAILABLE');
        }
        $page = $value instanceof ResourceReferencePage ? [
            'number' => $value->currentPage, 'last' => $value->lastPage,
            'perPage' => $value->perPage, 'total' => $value->total,
            'hasMore' => $value->currentPage < $value->lastPage,
        ] : null;
        $data = $value instanceof ResourceReferencePage ? $value->items
            : ($value instanceof ResolvedResourceReference ? [$value] : ($value === null ? [] : array_values($value)));

        return ['status' => 'success', 'data' => $data, 'page' => $page, 'error' => null];
    }

    private function referenceQueryException(Exception $error): array
    {
        if ($error instanceof AuthenticationException) {
            return $this->referenceQueryError('UNAUTHENTICATED', 401);
        }
        if ($error instanceof AuthorizationException) {
            $status = $error->status() ?? 403;
            if (! is_int($status) || $status < 400 || $status > 599) {
                $status = 403;
            }
            return $this->referenceQueryError($status === 401 ? 'UNAUTHENTICATED' : $this->referenceQueryStatusCode($status), $status);
        }
        if ($error instanceof ModelNotFoundException) {
            return $this->referenceQueryError('NOT_FOUND', 404);
        }
        if ($error instanceof ValidationException) {
            return $this->referenceQueryError('INVALID_QUERY', 422);
        }
        if ($error instanceof ReferenceQueryFailure) {
            $allowed = ['INVALID_ARGUMENT', 'INVALID_BATCH', 'INVALID_QUERY', 'UNAUTHENTICATED', 'UNSUPPORTED_IMPLEMENTATION',
                'PROTECTED_VALUES_UNAVAILABLE', 'PAGE_UNAVAILABLE', 'COMPLETE_RANGE_UNAVAILABLE', 'UPSTREAM_AUTHENTICATION_FAILED', 'FORBIDDEN',
                'NOT_FOUND', 'TIMEOUT', 'TOO_LARGE', 'RATE_LIMITED', 'QUERY_FAILED', 'RUNTIME_RESOURCE_READ_NOT_GRANTED',
                'RUNTIME_REFERENCE_DEADLINE_EXCEEDED', 'RUNTIME_REFERENCE_CYCLE', 'RUNTIME_REFERENCE_DEPTH_EXCEEDED'];
            if (! in_array($error->queryCode, $allowed, true)
                || ($error->httpStatus !== null && ($error->httpStatus < 400 || $error->httpStatus > 599))) {
                return $this->referenceQueryError('QUERY_FAILED');
            }

            return $this->referenceQueryError($error->queryCode, $error->httpStatus);
        }
        $properties = get_object_vars($error);
        $status = $error instanceof HttpExceptionInterface ? $error->getStatusCode() : ($properties['upstreamStatus'] ?? null);
        $status = is_int($status) && $status >= 400 && $status <= 599 ? $status : null;
        if ($error instanceof UnsupportedReferenceQuery) {
            return $this->referenceQueryError('UNSUPPORTED_IMPLEMENTATION');
        }
        if (isset($properties['failureCode']) && is_string($properties['failureCode'])) {
            $runtimeStatus = match ($properties['failureCode']) {
                'RUNTIME_RESOURCE_READ_NOT_GRANTED' => 503,
                'RUNTIME_REFERENCE_DEADLINE_EXCEEDED' => 504,
                'RUNTIME_REFERENCE_CYCLE', 'RUNTIME_REFERENCE_DEPTH_EXCEEDED' => 508,
                default => null,
            };
            if ($runtimeStatus !== null && $status === null) {
                return $this->referenceQueryError($properties['failureCode'], $runtimeStatus);
            }
        }
        $code = $status === null ? ($error instanceof InvalidArgumentException ? 'INVALID_ARGUMENT' : 'QUERY_FAILED')
            : $this->referenceQueryStatusCode($status);

        return $this->referenceQueryError($code, $status);
    }

    private function referenceQueryStatusCode(int $status): string
    {
        return match ($status) {
            400, 422 => 'INVALID_QUERY', 401 => 'UPSTREAM_AUTHENTICATION_FAILED', 403 => 'FORBIDDEN',
            404 => 'NOT_FOUND', 408, 504 => 'TIMEOUT', 413 => 'TOO_LARGE', 429 => 'RATE_LIMITED',
            default => 'QUERY_FAILED',
        };
    }

    private function referenceQueryError(string $code, ?int $httpStatus = null): array
    {
        return ['status' => 'error', 'data' => [], 'page' => null,
            'error' => ['code' => $code, 'httpStatus' => $httpStatus === 401 && $code === 'UPSTREAM_AUTHENTICATION_FAILED' ? 502 : $httpStatus]];
    }

    private function assertReferenceQuerySupported(string $method): void
    {
        if (! $this->referenceQuerySupports($method)) {
            throw new UnsupportedReferenceQuery;
        }
    }

    private function referenceQuerySupports(string $method): bool
    {
        return ! method_exists($this, 'supportsReferenceQueryOperation') || $this->supportsReferenceQueryOperation($method);
    }

    private function validReferenceIds(array $ids): bool
    {
        return array_filter($ids, static fn (mixed $id): bool => ! is_string($id)
            || $id === '' || mb_strlen($id) > 255) === [];
    }

    private function sameBatchScope(ResourceReferenceResolutionContext $a, ResourceReferenceResolutionContext $b): bool
    {
        return $a->actor === $b->actor && $a->legalEntity === $b->legalEntity
            && $a->operatingUnit === $b->operatingUnit && $a->purpose === $b->purpose && $a->sort === $b->sort;
    }

    private function isSimpleBatchSearch(array $options): bool
    {
        return array_diff(array_keys($options), ['search', 'limit']) === [] && is_string($options['search'] ?? null)
            && mb_strlen($options['search']) <= 160
            && (! array_key_exists('limit', $options) || (is_int($options['limit']) && $options['limit'] >= 1 && $options['limit'] <= 10000));
    }
}
