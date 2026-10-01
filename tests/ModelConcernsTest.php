<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Engines\NullEngine;
use Nexia\Laravel\Models\Concerns\HasDisplayLabel;
use Nexia\Laravel\Models\Concerns\HasPublicUuid;
use Nexia\Laravel\Models\Contracts\ScoutSearchEngineResolver;
use Nexia\Laravel\Models\Contracts\SearchResourceIdentityResolver;
use Nexia\Laravel\Models\NexiaEntityModel;
use Nexia\Laravel\Models\NexiaModel;
use Nexia\Laravel\Models\ScoutSearchResolverRegistry;
use Nexia\Laravel\Attachments\Contracts\RequiresMediaOwnerViewAuthorization;
use Spatie\MediaLibrary\HasMedia;

final class PublicUuidFixture extends Model
{
    use HasPublicUuid;
}

final class DisplayLabelFixture extends Model
{
    use HasDisplayLabel;

    public function displayLabel(): string
    {
        return 'Canonical label';
    }
}

final class MediaOwnerFixture extends NexiaModel implements RequiresMediaOwnerViewAuthorization {}

$public = new PublicUuidFixture;
assert($public->uniqueIds() === ['public_id']);
assert($public->getRouteKeyName() === 'public_id');

$labelled = new DisplayLabelFixture;
assert(in_array('display_label', $labelled->getAppends(), true));
assert($labelled->display_label === 'Canonical label');
assert(is_subclass_of(NexiaModel::class, Model::class));
assert(is_subclass_of(NexiaEntityModel::class, NexiaModel::class));
assert((new MediaOwnerFixture) instanceof RequiresMediaOwnerViewAuthorization);
assert((new MediaOwnerFixture) instanceof HasMedia);
assert(method_exists(MediaOwnerFixture::class, 'media'));
try {
    (new MediaOwnerFixture)->media();
    throw new RuntimeException('An isolated model exposed a local media relation.');
} catch (LogicException $exception) {
    assert(str_contains($exception->getMessage(), 'Local media is unavailable'));
}

Model::setEventDispatcher(new \Illuminate\Events\Dispatcher);
class MediaDeletionFixture extends NexiaModel
{
    public int $mediaDeletes = 0;

    public function deleteAllMedia(): self
    {
        $this->mediaDeletes++;
        return $this;
    }

    public function dispatchDeleting(): void
    {
        $this->fireModelEvent('deleting');
    }
}
class LocalMediaDeletionFixture extends MediaDeletionFixture {}
$isolated = new MediaDeletionFixture;
$isolated->dispatchDeleting();
assert($isolated->mediaDeletes === 0);
NexiaModel::enableLocalMedia();
$local = new LocalMediaDeletionFixture;
$local->dispatchDeleting();
assert($local->mediaDeletes === 1);
Model::unsetEventDispatcher();

$identityResolver = new class implements SearchResourceIdentityResolver
{
    public function tenantKey(): int|string|null
    {
        return 'tenant';
    }

    public function resourceKeyForModel(string $modelClass): ?string
    {
        return 'test.record';
    }
};
$engineResolver = new class implements ScoutSearchEngineResolver
{
    public function defaultEngine(): NullEngine
    {
        return new NullEngine;
    }

    public function databaseEngine(): NullEngine
    {
        return new NullEngine;
    }
};
ScoutSearchResolverRegistry::configure($identityResolver, $engineResolver);

assert(ScoutSearchResolverRegistry::identityResolver() === $identityResolver);
assert(ScoutSearchResolverRegistry::engineResolver() === $engineResolver);
// A database-only App host has no shared tenant search identity catalog.
ScoutSearchResolverRegistry::configure(null, $engineResolver);
assert(ScoutSearchResolverRegistry::identityResolver() === null);
assert(ScoutSearchResolverRegistry::engineResolver() === $engineResolver);
ScoutSearchResolverRegistry::configure($identityResolver, $engineResolver);
assert(! method_exists(NexiaEntityModel::class, 'configureSearchResourceIdentityResolver'));
assert(! method_exists(NexiaEntityModel::class, 'configureScoutSearchEngineResolver'));

echo "Model concern contracts passed.\n";
