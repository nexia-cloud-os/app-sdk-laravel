<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** Data-only conversion for the existing document/preparation DTOs. */
final class SignatureDocumentWire
{
    public static function plan(SignatureDocumentPlanSubmission $value): array
    {
        $source = $value->source;
        if (! $source instanceof PublishedTemplateSignatureDocumentSource && ! $source instanceof UploadedPdfSignatureDocumentSource) {
            throw new InvalidArgumentException('Unsupported signature document source.');
        }
        $data = get_object_vars($source);
        if ($source instanceof UploadedPdfSignatureDocumentSource) {
            $data['participants'] = array_map(static fn ($participant) => $participant->toArray(), $source->participants);
            $data['fields'] = array_map(static fn ($field) => $field->toArray(), $source->fields);
        }
        return [...get_object_vars($value), 'legalEntity' => $value->legalEntity->publicId(), 'actor' => $value->actor->publicId(),
            'subject' => $value->subject->toArray(), 'source' => ['kind' => $source->kind()->value, 'data' => $data],
            'trustedAssets' => array_map(static fn ($asset) => $asset->toArray(), $value->trustedAssets)];
    }

    public static function restorePlan(array $data, LegalEntity $entity, Actor $actor): SignatureDocumentPlanSubmission
    {
        if (($data['legalEntity'] ?? null) !== $entity->publicId() || ($data['actor'] ?? null) !== $actor->publicId()) {
            throw new InvalidArgumentException('Signature plan context differs.');
        }
        $source = $data['source']['data'];
        $data['source'] = match ($data['source']['kind']) {
            SignatureDocumentSourceKind::PublishedTemplate->value => new PublishedTemplateSignatureDocumentSource(...$source),
            SignatureDocumentSourceKind::UploadedPdf->value => new UploadedPdfSignatureDocumentSource(...[...$source,
                'participants' => array_map(SignatureParticipantSnapshot::fromArray(...), $source['participants']),
                'fields' => array_map(SignatureFieldDefinition::fromArray(...), $source['fields'])]),
            default => throw new InvalidArgumentException('Unsupported signature document source.'),
        };
        return new SignatureDocumentPlanSubmission(...[...$data, 'legalEntity' => $entity, 'actor' => $actor,
            'subject' => ResourceRef::fromArray($data['subject']),
            'trustedAssets' => array_map(SignatureTrustedAssetSelection::fromArray(...), $data['trustedAssets'])]);
    }

    public static function preparedResult(PreparedSignableDocumentResult $result): array
    {
        return [...get_object_vars($result), 'status' => $result->status->value, 'signableDocumentStatus' => $result->signableDocumentStatus?->value];
    }

    public static function restorePreparedResult(array $data): PreparedSignableDocumentResult
    {
        return new PreparedSignableDocumentResult(...[...$data, 'status' => PreparedSignableDocumentStatus::from($data['status']),
            'signableDocumentStatus' => $data['signableDocumentStatus'] === null ? null : SignableDocumentStatus::from($data['signableDocumentStatus'])]);
    }

    public static function query(SignatureTemplateCatalogQuery|SignatureTemplateParticipantAssignmentQuery $query): array
    {
        return [...get_object_vars($query), 'legalEntity' => $query->legalEntity->publicId(),
            'actor' => $query->actor->publicId(), 'subject' => $query->subject->toArray()];
    }

    public static function submission(CurrentBoundSignableDocumentSubmission|SignatureRequestPreparationSubmission $value): array
    {
        $data = [...get_object_vars($value), 'legalEntity' => $value->legalEntity->publicId(),
            'actor' => $value->actor->publicId(), 'subject' => $value->subject->toArray()];
        if ($value instanceof CurrentBoundSignableDocumentSubmission) {
            $data['trustedAssets'] = array_map(static fn ($asset) => $asset->toArray(), $value->trustedAssets);
        }
        return $data;
    }

    public static function restoreSubmission(array $data, LegalEntity $entity, Actor $actor, bool $preparation = false): CurrentBoundSignableDocumentSubmission|SignatureRequestPreparationSubmission
    {
        if (($data['legalEntity'] ?? null) !== $entity->publicId() || ($data['actor'] ?? null) !== $actor->publicId()) {
            throw new InvalidArgumentException('Signature context differs.');
        }
        $data['legalEntity'] = $entity;
        $data['actor'] = $actor;
        $data['subject'] = ResourceRef::fromArray($data['subject']);
        if ($preparation) return new SignatureRequestPreparationSubmission(...$data);
        $data['trustedAssets'] = array_map(SignatureTrustedAssetSelection::fromArray(...), $data['trustedAssets']);
        return new CurrentBoundSignableDocumentSubmission(...$data);
    }

    public static function result(CurrentBoundSignableDocumentResult $value): array
    {
        return [...get_object_vars($value), 'submissionStatus' => $value->submissionStatus->value, 'documentStatus' => $value->documentStatus->value];
    }

    public static function restoreResult(array $data): CurrentBoundSignableDocumentResult
    {
        return new CurrentBoundSignableDocumentResult(...[...$data,
            'submissionStatus' => SignableDocumentSubmissionStatus::from($data['submissionStatus']),
            'documentStatus' => SignableDocumentStatus::from($data['documentStatus'])]);
    }

    public static function summary(SignableDocumentSummary $value): array
    {
        return [...get_object_vars($value), 'status' => $value->status->value,
            'resolvedFields' => array_map(get_object_vars(...), $value->resolvedFields),
            'trustedAssets' => array_map(static fn ($asset) => $asset->toArray(), $value->trustedAssets)];
    }

    public static function restoreSummary(array $data): SignableDocumentSummary
    {
        return new SignableDocumentSummary(...[...$data, 'status' => SignableDocumentStatus::from($data['status']),
            'resolvedFields' => array_map(static fn (array $field) => new SignableDocumentResolvedField(...$field), $data['resolvedFields']),
            'trustedAssets' => array_map(SignatureTrustedAssetSnapshot::fromArray(...), $data['trustedAssets'])]);
    }
}
