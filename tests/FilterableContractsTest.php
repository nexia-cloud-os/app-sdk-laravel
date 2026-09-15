<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Illuminate\Database\Eloquent\Model;
use Nexia\Laravel\Filters\Contracts\Sort;
use Nexia\Laravel\Filters\Sorts\Column;
use Nexia\Laravel\Filters\Types\Exact;
use Nexia\Laravel\Models\Concerns\Filterable;
use Nexia\Laravel\Models\Concerns\UsesFilterableScoutSearch;
use Nexia\Laravel\Resources\ResourceListFields;

final class FilterableSearchFixture extends Model
{
    use UsesFilterableScoutSearch;

    public function resourceListFields(): ResourceListFields
    {
        return ResourceListFields::make()->field('name', searchable: true);
    }
}

$catalog = new class extends Model
{
    use Filterable;

    public function resourceListFields(): ResourceListFields
    {
        return ResourceListFields::make()
            ->field(
                'status',
                filter: new Exact('status'),
                multiple: true,
                label: 'record.status.label',
                options: [['value' => 'ACTIVE', 'label' => 'Active']],
            )
            ->field('created_at', sortable: true)
            ->field('display', sortable: new Column('name'))
            ->field('name', searchable: true);
    }
};

assert($catalog->listSchema() === [
    'sortable' => ['created_at', 'display'],
    'filterable' => [[
        'key' => 'status',
        'multiple' => true,
        'label' => 'record.status.label',
        'options' => [['value' => 'ACTIVE', 'label' => 'Active']],
    ]],
    'searchable' => ['name'],
]);
assert($catalog->resourceListFields()->sortCatalog()['display'] instanceof Sort);

$duplicateRejected = false;
try {
    ResourceListFields::make()
        ->field('name', searchable: true)
        ->field('name', searchable: true);
} catch (InvalidArgumentException) {
    $duplicateRejected = true;
}
assert($duplicateRejected);

$emptyCapabilityRejected = false;
try {
    ResourceListFields::make()->field('name');
} catch (InvalidArgumentException) {
    $emptyCapabilityRejected = true;
}
assert($emptyCapabilityRejected);

assert(in_array(UsesFilterableScoutSearch::class, class_uses_recursive(FilterableSearchFixture::class), true));
assert(FilterableSearchFixture::decomposeHangulJamo('박태관') === 'ㅂㅏㄱㅌㅐㄱㅘㄴ');
assert(FilterableSearchFixture::decomposeHangulJamo('값') === 'ㄱㅏㅄ');
assert(FilterableSearchFixture::decomposeHangulJamo('A-1 바') === 'a-1 ㅂㅏ');

echo "Filterable contracts passed.\n";
