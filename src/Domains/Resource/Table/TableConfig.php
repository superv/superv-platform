<?php

namespace SuperV\Platform\Domains\Resource\Table;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use SuperV\Platform\Domains\Context\Context;
use SuperV\Platform\Domains\Resource\Action\EditEntryAction;
use SuperV\Platform\Domains\Resource\Contracts\ProvidesQuery;
use SuperV\Platform\Domains\Resource\Field\Field;
use SuperV\Platform\Domains\UI\Components\TableComponent;
use SuperV\Platform\Support\Composition;

class TableConfig
{
    public $query;

    protected $uuid;

    /**
     * Table title
     *
     * @var string
     */
    protected $title;

    /**
     * @var array
     */
    protected $hiddenFields = [];

    /**
     * @var Collection
     */
    protected $contextActions;

    /**
     * @var Collection
     */
    protected $rowActions;

    protected $url;

    protected $built = false;

    /** @var \SuperV\Platform\Domains\Resource\Contracts\ProvidesQuery */
    protected $queryProvider;

    /** @var \SuperV\Platform\Domains\Resource\Contracts\ProvidesFields */
    protected $fieldsProvider;

    /** @var \SuperV\Platform\Domains\Context\Context */
    protected $context;

    public function build(): self
    {
        $this->uuid = Str::uuid();

        $this->contextActions = collect($this->contextActions ?? [])
            ->map(function ($action) {
                /** @var \SuperV\Platform\Domains\Resource\Action\Action $action */
                if (is_string($action)) {
                    $action = $action::make();
                }

                if ($this->context) {
                    $this->context->add($action)->apply();
                }

                return $action->makeComponent();
            });

        $this->rowActions = collect($this->rowActions ?? [EditEntryAction::class]);

        $this->url = sv_url('sv/tables/'.$this->uuid());

        $this->built = true;

        $this->cache();

        return $this;
    }

    public function makeComponent()
    {
        return TableComponent::from($this);
    }

    public function newQuery()
    {
        return $this->queryProvider->newQuery();
    }

    public function compose()
    {
        if (! $this->isBuilt()) {
            throw new Exception('Table Config is not built yet');
        }

        $composition = new Composition([
            'config' => [
                'context' => [
                    'actions' => $this->contextActions,
                ],
                'meta'    => [
                    'columns' => $this->getFields()
                                      ->map(function ($field) {
                                          return ['label' => $field->getLabel(), 'name' => $field->getName()];
                                      })
                                      ->all(),
                ],
                'dataUrl' => $this->getDataUrl(),
            ],
        ]);

        return $composition;
    }

    public function makeTable($build = true): Table
    {
        $table = Table::config($this);
        if (! $build) {
            return $table;
        }

        return $table->build();
    }

    public function getUrl()
    {
        return $this->url.'/config';
    }

    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function getDataUrl()
    {
        return $this->url.'/data';
    }

    public function removeColumn(string $name)
    {
        $this->hiddenFields[] = $name;
    }

    public function getFields(): Collection
    {
        return $this->fieldsProvider->provideFields()
                                    ->map(function (Field $field) {
                                        if ($field->getConfigValue('hide.table') === true) {
                                            return null;
                                        }
                                        if (in_array($field->getName(), $this->hiddenFields)) {
                                            return null;
                                        }

                                        return $field;
                                    })
                                    ->filter();
    }

    public function getRowActions(): ?Collection
    {
        return $this->rowActions;
    }

    public function setRowActions($rowActions): TableConfig
    {
        $this->rowActions = $rowActions;

        return $this;
    }

    public function isBuilt(): bool
    {
        return $this->built;
    }

    protected function validate(): void
    {
        if ($this->isBuilt()) {
            throw new Exception('Config is already built');
        }
    }

    public function cache()
    {
        cache()->forever($this->cacheKey(), serialize($this));
    }

    public function cacheKey(): string
    {
        return 'sv:tables:'.$this->uuid();
    }

    public function setQueryProvider(ProvidesQuery $queryProvider): self
    {
        $this->queryProvider = $queryProvider;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setFieldsProvider($fieldsProvider): self
    {
        $this->fieldsProvider = $fieldsProvider;

        return $this;
    }

    public function getContextActions(): Collection
    {
        return $this->contextActions;
    }

    public function setContextActions($contextActions)
    {
        $this->contextActions = $contextActions;

        return $this;
    }

    public function setContext(Context $context)
    {
        $this->context = $context;

        return $this;
    }

    public function uuid()
    {
        return $this->uuid;
    }

    public static function fromCache($uuid): ?TableConfig
    {
        if ($config = cache('sv:tables:'.$uuid)) {
            $config = unserialize($config);

            return $config;
        }

        return null;
    }
}