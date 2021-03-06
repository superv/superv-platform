<?php

namespace SuperV\Platform\Domains\Resource\UI;

use Current;
use Event;
use SuperV\Platform\Domains\Resource\Resource;
use SuperV\Platform\Domains\UI\Components\Component;
use SuperV\Platform\Domains\UI\Page\ResourcePage;

class ResourceDashboard
{
    /**
     * @var \SuperV\Platform\Domains\Resource\Resource
     */
    protected $resource;

    /**
     * @var string
     */
    protected $section;

    /**
     * @var \SuperV\Platform\Domains\UI\Page\ResourcePage
     */
    protected $page;

    public function __construct(Resource $resource, string $section = null)
    {
        $this->resource = $resource;
        $this->section = $section;

        $page = ResourcePage::make(__($resource->getLabel()));
        $page->setResource($resource);
        $page->setDefaultSection('all');
        $page->setSelectedSection($section);

        $this->page = $page;

        Event::dispatch($resource->getIdentifier().'.pages:dashboard.events:resolved', compact('page', 'resource'));
    }

    public function render()
    {
        $resource = $this->resource;
        $section = $this->section;
        $page = $this->page;

        $page->addBlock(Component::make('sv-router-portal')->setProps([
            'name' => $resource->getIdentifier(),
        ]));

        if (Current::user()->can($this->resource->getChildIdentifier('actions', 'list'))) {
            $page->addSection([
                'identifier' => 'all',
                'title'      => sv_trans('All'),
                'url'        => $resource->router()->dashboard('table'),
                'target'     => 'portal:'.$resource->getIdentifier(),
                'default'    => ! $section || $section === 'all',
            ]);
        }

        if (Current::user()->can($this->resource->getChildIdentifier('actions', 'create'))) {
            if ($page->isCreatable() && empty($page->getActions())) {
                $page->addSection([
                    'identifier' => 'create',
                    'title'      => sv_trans('Create'),
                    'url'        => $resource->router()->createForm(),
                    'target'     => 'portal:'.$resource->getIdentifier(),
                    'default'    => $section === 'create',
                ]);
            }
        }

        $page->setMeta('url', 'sv/res/'.$resource->getIdentifier());

        $page = $page->build(['res' => $resource->toArray()]);

        Event::dispatch($resource->getIdentifier().'.pages:dashboard.events:rendered', compact('page', 'resource'));

        return $page->toResponse([]);
    }

    /** @return static */
    public static function resolve()
    {
        return new static(...func_get_args());
    }
}
