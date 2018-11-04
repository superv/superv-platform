<?php

namespace SuperV\Platform\Domains\Resource\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use SuperV\Platform\Domains\Resource\Field\Field;
use SuperV\Platform\Domains\Resource\Model\ResourceEntryModel;
use SuperV\Platform\Domains\Resource\Resource;
use SuperV\Platform\Support\Concerns\HasOptions;

class Table
{
    use HasOptions;

    /**
     * @var TableConfig
     */
    protected $config;

    /** @var Resource */
    protected $resource;

    /** @var Builder */
    protected $query;

    /** @var Collection */
    protected $rows;

    /** @var array */
    protected $pagination;

    protected $built = false;

    public function __construct()
    {
        $this->options = collect();
        $this->rows = collect();
    }

    public function build(): self
    {
        $this->resource = $this->config->getResource();

        $query = $this->newQuery();

        $this->config->getColumns()->map(function (Field $field) use ($query) {
            $field->buildForView($query);
        });

        $entries = $this->fetchEntries($query)
                        ->map(function (ResourceEntryModel $entry) {
                            return $this->resource->fresh()->setEntry($entry);
                        });

        $this->buildRows($entries);

        $this->built = true;

        return $this;
    }

    protected function fetchEntries(Builder $query)
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->getOption('limit', 10));
        $countBefore = $paginator->getCollection()->count();
        $entries = $paginator->getCollection();

        // Repaginate if guard filtered some of the entries..
        // Not ideal but should do the trick for now
        if ($countBefore !== $entries->count()) {
            $paginator = new LengthAwarePaginator(
                $entries,
                $paginator->total() - ($countBefore - $entries->count()),
                $paginator->perPage(),
                $paginator->currentPage()
            );
        }

        $this->pagination = $paginator->toArray();

        unset($this->pagination['data']);

        return $entries;
    }

    protected function buildRows(Collection $entries)
    {
        $entries->map(function ($entry) {
            $row = new TableRow($this, $entry);
            $this->rows->push($row->build());
        });
    }

    public function getRows(): Collection
    {
        return $this->rows;
    }

    public function newQuery()
    {
        return $this->resource->resolveModel()->newQuery()->select($this->resource->getSlug().'.*');
    }

    public function setResource(Resource $resource): Table
    {
        $this->resource = $resource;

        return $this;
    }

    public function getColumns(): Collection
    {
        return $this->config->getColumns();
    }

    public function getActions(): Collection
    {
        return $this->config->getActions();
    }

    public function uuid()
    {
        return $this->config->uuid();
    }

    public function url()
    {
        return $this->config->getUrl();
    }

    public function compose(): array
    {
        return (new TableData($this))->toArray();
    }

    public function getConfig(): TableConfig
    {
        return $this->config;
    }

    public function setConfig(TableConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function isBuilt(): bool
    {
        return $this->built;
    }

    public static function config(TableConfig $config): self
    {
        return app(self::class)->setConfig($config);
    }
}