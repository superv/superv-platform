<?php

namespace SuperV\Platform\Domains\Resource\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use SuperV\Platform\Domains\Resource\Field\Field;
use SuperV\Platform\Domains\Resource\Field\FieldModel;
use SuperV\Platform\Domains\Resource\Field\Jobs\AttachTypeToField;
use SuperV\Platform\Domains\Resource\Field\Types\FieldType;
use SuperV\Platform\Domains\Resource\Model\ResourceEntry;
use SuperV\Platform\Domains\Resource\Model\ResourceEntryModel;
use SuperV\Platform\Support\Concerns\HasOptions;

class Table
{
    use HasOptions;

    /**
     * @var TableConfig
     */
    protected $config;

    /** @var Builder */
    protected $query;

    /** @var \SuperV\Platform\Domains\Resource\Table\TableRow|\Illuminate\Support\Collection */
    protected $rows;

    /** @var \SuperV\Platform\Domains\Resource\Field\Field[]|\Illuminate\Support\Collection */
    protected $fields;

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
        $query = $this->config->newQuery();

        $this->fields = $this->config->getFields()->map(function (Field $field) use ($query) {
            $fieldType = FieldType::fromEntry(FieldModel::withUuid($field->uuid()));
            AttachTypeToField::dispatch($fieldType, $field);
            $fieldType->buildForView($query);

            return $field;
        });

        $this->fetchEntries($query)
             ->map(function (ResourceEntryModel $entry) {
                 $row = new TableRow($this, ResourceEntry::make($entry));
                 $this->rows->push($row->build());
             });

        $this->built = true;

        return $this;
    }

    protected function fetchEntries($query)
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

    public function getRows(): Collection
    {
        return $this->rows;
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function getActions(): Collection
    {
        return $this->config->getActions();
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

    public function setQuery($query): Table
    {
        $this->query = $query;

        return $this;
    }

    public function uuid()
    {
        return $this->config->uuid();
    }

    public static function config(TableConfig $config): self
    {
        return app(self::class)->setConfig($config);
    }
}