<?php

namespace SuperV\Platform\Domains\Resource\Field\Types;

use Closure;
use Current;
use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Resource\Contracts\InlinesForm;
use SuperV\Platform\Domains\Resource\Contracts\ProvidesFilter;
use SuperV\Platform\Domains\Resource\Field\Contracts\HandlesRpc;
use SuperV\Platform\Domains\Resource\Field\Contracts\HasPresenter;
use SuperV\Platform\Domains\Resource\Field\Contracts\RequiresDbColumn;
use SuperV\Platform\Domains\Resource\Field\Contracts\SortsQuery;
use SuperV\Platform\Domains\Resource\Field\FieldType;
use SuperV\Platform\Domains\Resource\Filter\SelectFilter;
use SuperV\Platform\Domains\Resource\Form\Contracts\FormInterface;
use SuperV\Platform\Domains\Resource\Relation\RelationConfig;
use SuperV\Platform\Domains\Resource\ResourceFactory;
use SuperV\Platform\Support\Composer\Payload;

class BelongsToField extends FieldType implements
    RequiresDbColumn,
    ProvidesFilter,
    InlinesForm,
    HandlesRpc,
    HasPresenter,
    SortsQuery
{
    /** @var \SuperV\Platform\Domains\Resource\Resource */
    protected $relatedResource;

    /** @var array */
    protected $options;

    /** @var RelationConfig */
    protected $relationConfig;

    protected function boot()
    {
//        $this->field->on('form.presenting', $this->presenter());
        $this->field->on('form.composing', $this->formComposer());

        $this->field->on('view.presenting', $this->viewPresenter());
        $this->field->on('view.composing', $this->viewComposer());

        $this->field->on('table.presenting', $this->presenter());
        $this->field->on('table.composing', $this->tableComposer());
        $this->field->on('table.querying', function ($query) {
            $query->with($this->getName());
        });
    }

    public function getColumnName(): ?string
    {
        return $this->getConfigValue('foreign_key', $this->getName().'_id');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param                                       $direction
     * @throws \Exception
     */
    public function sortQuery($query, $direction)
    {
        $parentResource = ResourceFactory::make($this->field->identifier()->parent());
        $parentTable = $parentResource->config()->getTable();

        $relation = RelationConfig::create($this->field->getType(), $this->field->getConfig());

        $relatedResource = ResourceFactory::make($relation->getRelatedResource());
        $relatedTable = $relatedResource->config()->getTable();

        $labelField = $relatedResource->fields()->getEntryLabelField();
        $labelFieldColumName = $labelField ? $labelField->getColumnName() : $relatedResource->config()->getKeyName();

        $orderBy = $relatedTable.'_1.'.$labelFieldColumName;

        $joinType = 'leftJoin';
        $query->getQuery()
              ->{$joinType}($relatedTable." AS ".$relatedTable."_1",
                  $relatedTable.'_1.'.$relatedResource->getKeyName(), '=', $parentTable.'.'.$relation->getForeignKey());

        $query->orderBy($orderBy, $direction);
    }

    public function makeFilter(?array $params = [])
    {
        $this->buildOptions($params['query'] ?? null);

        return SelectFilter::make($this->getName(), $this->getRelatedResource()->getSingularLabel())
                           ->setAttribute($this->getColumnName())
                           ->setOptions($this->options)
                           ->setDefaultValue($params['default_value'] ?? null);
    }

    public function getRpcResult(array $params, array $request = [])
    {
        if (! $method = $params['method'] ?? null) {
            return null;
        }

        if (method_exists($this, $method = 'rpc'.studly_case($method))) {
            return call_user_func_array([$this, $method], [$params, $request]);
        }
    }

    public function buildOptions(?array $queryParams = [])
    {
        $query = $this->getRelatedResource()->newQuery();
        if ($queryParams) {
            $query->where($queryParams);
        }

        $entryLabel = $this->getRelatedResource()->config()->getEntryLabel(sprintf("#%s", $this->getRelatedResource()->getKeyName()));

        if ($entryLabelField = $this->getRelatedResource()->fields()->getEntryLabelField()) {
            $query->orderBy($entryLabelField->getColumnName(), 'ASC');
        }

        $this->options = $query->get()->map(function (EntryContract $item) use ($entryLabel) {
            if ($keyName = $this->relatedResource->config()->getKeyName()) {
                $item->setKeyName($keyName);
            }

            return ['value' => $item->getId(), 'text' => sv_parse($entryLabel, $item->toArray())];
        })->all();

        return $this->options;
    }

    /**
     * @return \SuperV\Platform\Domains\Resource\Resource
     * @throws \Exception
     */
    public function getRelatedResource()
    {
        if (! $this->relatedResource) {
            $this->relatedResource = ResourceFactory::make($this->getRelationConfig()->getRelatedResource());
        }

        return $this->relatedResource;
    }

    public function getPresenter(): Closure
    {
        return function (EntryContract $entry) {
            if (! $entry->relationLoaded($this->getName())) {
                $entry->load($this->getName());
            }

            if ($relatedEntry = $entry->getRelation($this->getName())) {
                return sv_resource($relatedEntry)->getEntryLabel($relatedEntry);
            }
        };
    }

    public function getRelationConfig(): RelationConfig
    {
        if (! $this->relationConfig) {
            $this->relationConfig = RelationConfig::create($this->field->getType(), $this->field->getConfig());
        }

        return $this->relationConfig;
    }

    public function inlineForm(FormInterface $parent, array $config = []): void
    {
        $this->field->hide();

        $parent->fields()->addFieldFromArray([
            'type'   => 'sub_form',
            'name'   => $this->getName(),
            'config' => array_merge([
                'parent_type' => $this,
                'resource'    => $this->getRelatedResource()->getIdentifier(),
            ], $config, $this->getConfigValue('inline', [])),
        ]);
    }

    public function formComposer()
    {
        return function (Payload $payload, FormInterface $form, ?EntryContract $entry = null) {
            if ($entry) {
                if ($relatedEntry = $entry->{$this->getName()}()->newQuery()->first()) {
                    $payload->set('meta.link', $relatedEntry->router()->dashboardSPA());
                }
            }
            $this->relatedResource = $this->getRelatedResource();

            $options = $this->getConfigValue('meta.options');
            if (! is_null($options)) {
                $payload->set('meta.options', $options);
            } else {
//                $url = sprintf("sv/forms/%s/fields/%s/options", $this->field->getForm()->uuid(), $this->getName());

                $route = $form->isPublic() ? 'sv::public_forms.fields' : 'sv::forms.fields';
                $url = sv_route($route, [
                    'form'  => $this->field->getForm()->getIdentifier(),
                    'field' => $this->getName(),
                    'rpc'   => 'options',
                ]);
                $payload->set('meta.options', $url);
            }
            $payload->set('placeholder', __('Select :Object', ['object' => $this->relatedResource->getSingularLabel()]));
        };
    }

    public function rpcOptions(array $params, array $request = [])
    {
        $this->relatedResource = $this->getRelatedResource();

        return $this->buildOptions($request['query'] ?? []);
    }

    public function presenter()
    {
        return function (EntryContract $entry) {
            if (! $entry->relationLoaded($this->getName())) {
                $entry->load($this->getName());
            }

            return $this->getRelatedEntryLabel($entry->getRelation($this->getName()));
        };
    }

    public function viewPresenter()
    {
        return function (EntryContract $entry) {
            return $this->getRelatedEntryLabel($entry->{$this->getName()}()->newQuery()->first());
        };
    }

    public function viewComposer()
    {
        return function (Payload $payload, EntryContract $entry) {
            $this->setMetaLink($entry->{$this->getName()}()->newQuery()->first(), $payload);
        };
    }

    public function tableComposer()
    {
        return function (Payload $payload, EntryContract $entry) {
            if (! $entry->relationLoaded($this->getName())) {
                $entry->load($this->getName());
            }
            $this->setMetaLink($entry->getRelation($this->getName()), $payload);
        };
    }

    protected function getRelatedEntryLabel(?EntryContract $relatedEntry = null)
    {
        if (is_null($relatedEntry)) {
            return null;
        }

        return sv_resource($relatedEntry)->getEntryLabel($relatedEntry);
    }

    protected function setMetaLink(?EntryContract $relatedEntry = null, Payload $payload)
    {
        if (is_null($relatedEntry)) {
            return;
        }
        if (Current::user()->canNot($relatedEntry->getResourceIdentifier())) {
            return;
        }
        $payload->set('meta.link', $relatedEntry->router()->dashboardSPA());
    }
}
