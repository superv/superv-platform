<?php

namespace SuperV\Platform\Domains\UI\Page;

use Illuminate\Contracts\Support\Responsable;
use SuperV\Platform\Domains\Resource\Contracts\ProvidesUIComponent;
use SuperV\Platform\Domains\UI\Components\ComponentContract;
use SuperV\Platform\Domains\UI\Components\PageComponent;
use SuperV\Platform\Domains\UI\Jobs\MakeComponentTree;

class Page implements ProvidesUIComponent, Responsable
{
    protected $uuid;

    protected $meta = ['header' => true];

    protected $blocks = [];

    protected $actions = [];

    protected $sections = [];

    protected $selectedSection;

    protected $defaultSection;

    protected $tokens;

    protected $component;

    protected $componentName = 'sv-page';

    protected $built = false;

    protected function __construct(string $title)
    {
        $this->boot();
        $this->meta['title'] = $title;
    }

    protected function boot()
    {
        $this->uuid = uuid();
    }

    public function build($tokens = [])
    {
        $this->tokens = $tokens;

        $this->component = MakeComponentTree::dispatch($this);

        $this->built = true;

        return $this;
    }

    public function toResponse($request)
    {
        return response()->json([
            'data' => sv_compose($this->component, $this->tokens),
        ]);
    }

    public function addBlock($block)
    {
        $this->blocks[] = $block;

        return $this;
    }

    public function addSection($section)
    {
        $this->sections[] = $section;

        return $this;
    }

    public function addBlocks(array $blocks = [])
    {
        $this->blocks = array_merge($this->blocks, $blocks);

        return $this;
    }

    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function makeComponent(): ComponentContract
    {
        return PageComponent::from($this)->setName($this->componentName);
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function setMeta($key, $value)
    {
        array_set($this->meta, $key, $value);

        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function setActions($actions): Page
    {
        $this->actions = wrap_array($actions);

        return $this;
    }

    public function addAction($action)
    {
        $this->actions[] = $action;

        return $this;
    }

    public function setComponentName(string $componentName): Page
    {
        $this->componentName = $componentName;

        return $this;
    }

    /**
     * @return array
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    public function setParent($parent)
    {
        return $this->setMeta('parent', $parent);
    }

    public function setSelectedSection($selectedSection)
    {
        $this->selectedSection = $selectedSection;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSelectedSection()
    {
        return $this->selectedSection;
    }

    public function setDefaultSection($defaultSection)
    {
        $this->defaultSection = $defaultSection;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultSection()
    {
        return $this->defaultSection;
    }

    /**
     * @return bool
     */
    public function isBuilt(): bool
    {
        return $this->built;
    }

    public function getComponent(): ComponentContract
    {
        return $this->component;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public static function make(string $title)
    {
        return new static($title);
    }
}

