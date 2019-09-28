<?php

namespace SuperV\Platform\Domains\Resource\Form;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use SuperV\Platform\Contracts\Dispatcher;
use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Resource\Field\FieldFactory;
use SuperV\Platform\Domains\Resource\Field\FieldModel;
use SuperV\Platform\Domains\Resource\Resource;
use SuperV\Platform\Exceptions\PlatformException;

class FormBuilder
{
    /** @var \SuperV\Platform\Domains\Resource\Form\FormModel */
    protected $formEntry;

    /** @var \Illuminate\Http\Request */
    protected $request;

    /** @var \SuperV\Platform\Domains\Resource\Resource */
    protected $resource;

    /** @var \SuperV\Platform\Domains\Database\Model\Contracts\EntryContract */
    protected $entry;

    /**
     * @var \SuperV\Platform\Contracts\Dispatcher
     */
    protected $dispatcher;

    /**
     * @var int
     */
    protected $entryId;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getForm(): EntryForm
    {
//        if (! $this->formEntry->isPublic() && $this->getRequest()) {
//            app(PlatformAuthenticate::class)->guard($this->getRequest(), 'sv-api');
//        }

        $form = EntryForm::resolve($this->formEntry->getIdentifier());

        if ($this->resource = $this->formEntry->getOwnerResource()) {
            if (! $entry = $this->getEntry()) {
                if ($this->entryId) {
                    $entry = $this->resource->find($this->entryId);
                }
            }
            $form->setEntry($entry ?? $this->resource->newEntryInstance());
        }

        $form->setRequest($this->getRequest());

        $form->setFields($this->buildFields($this->formEntry->getFormFields()))
             ->setUrl(sv_url()->path())
             ->make($this->formEntry->getIdentifier());

        if ($this->resource && $callback = $this->resource->getCallback('creating')) {
            app()->call($callback, ['form' => $form]);
        }

        $this->dispatcher->dispatch($this->formEntry->getIdentifier().'.events:resolved', $form);

        return $form;
    }

    /**
     * Rebuild resource fields with FormField
     * and inject the resource
     *
     * @param \Illuminate\Support\Collection $fields
     * @return \Illuminate\Support\Collection
     */
    public function buildFields(Collection $fields)
    {
        $fields = $fields->map(function (FieldModel $field) {
            $field = FieldFactory::createFromEntry($field, FormField::class);

            if ($this->resource) {
                $field->setResource($this->resource);
            }

            return $field;
        });

        return $fields;
    }

    public function getEntry(): ?EntryContract
    {
        return $this->entry;
    }

    public function setEntry(?EntryContract $entry = null): FormBuilder
    {
        $this->entry = $entry;

        return $this;
    }

    public function setEntryId(int $entryId)
    {
        $this->entryId = $entryId;

        return $this;
    }

    public function setFormEntry(FormModel $formEntry): FormBuilder
    {
        $this->formEntry = $formEntry;

        return $this;
    }

    public function setRequest($request): FormBuilder
    {
        if (is_array($request)) {
            $request = new Request($request);
        }
        $this->request = $request;

        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request ?? app('request');
    }

    public function getResource(): ?Resource
    {
        return $this->resource;
    }

    /**
     * @return \SuperV\Platform\Domains\Resource\Form\FormModel
     */
    public function getFormEntry(): \SuperV\Platform\Domains\Resource\Form\FormModel
    {
        return $this->formEntry;
    }

    /** @return static */
    public static function resolve()
    {
        return app(static::class);
    }

    public static function fromResource($resource): FormBuilder
    {
        $formIdentifier = $resource->getIdentifier().'.forms:default';
        $builder = FormBuilder::createFrom($formIdentifier);

        return $builder;
    }
    public static function createFrom($formEntry): FormBuilder
    {
        if (is_string($identifier = $formEntry)) {
            if (! $formEntry = FormModel::withIdentifier($identifier)) {
                PlatformException::runtime('Form entry not found: '.$identifier);
            }
        }

        return static::resolve()->setFormEntry($formEntry);
    }
}
