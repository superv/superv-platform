<?php

namespace SuperV\Platform\Domains\Resource\Http\Controllers;

use SuperV\Platform\Domains\Resource\Contracts\AcceptsParentEntry;
use SuperV\Platform\Domains\Resource\Contracts\ProvidesForm;
use SuperV\Platform\Domains\Resource\Contracts\ProvidesTable;
use SuperV\Platform\Domains\Resource\Form\FormConfig;
use SuperV\Platform\Domains\Resource\Http\ResolvesResource;
use SuperV\Platform\Domains\Resource\Relation\Relation;
use SuperV\Platform\Domains\UI\Nucleo\SvBlock;
use SuperV\Platform\Domains\UI\Page\Page;
use SuperV\Platform\Http\Controllers\BaseApiController;

class ResourceController extends BaseApiController
{
    use ResolvesResource;

    public function create()
    {
        $resource = $this->resolveResource();
        $form = FormConfig::make()
                          ->setUrl($resource->route('store'))
                          ->addGroup(
                              $resource->getFields(),
                              $resource->newEntryInstance(),
                              $resource->getHandle()
                          )
                          ->makeForm();

        $page = Page::make('Create new '.$resource->getSingularLabel());
        $page->addBlock($form);

        return $page->build();
    }

    public function edit()
    {
        $resource = $this->resolveResource();
        $form = FormConfig::make()
                          ->setUrl($this->entry->route('update'))
                          ->addGroup(
                              $fields = $this->resolveResource()->getFields(),
                              $entry = $this->entry,
                              $handle = $this->resolveResource()->getHandle()
                          )
                          ->makeForm();

        // main edit form
        $editorTab = SvBlock::make('sv-form')->setProps($form->compose());

        $tabs = sv_tabs()->addTab(sv_tab('Edit', $editorTab)->autoFetch());

        // make forms
        $resource->getRelations()
                 ->filter(function (Relation $relation) { return $relation instanceof ProvidesForm; })
                 ->map(function (ProvidesForm $formProvider) use ($tabs) {
                     if ($formProvider instanceof AcceptsParentEntry) {
                         $formProvider->acceptParentEntry($this->entry);
                     }
                     $form = $formProvider->makeForm();

                     return $tabs->addTab(sv_tab($formProvider->getFormTitle(), SvBlock::make('sv-form')->setProps($form->compose())));
                 });

        // make tables
        $resource->getRelations()
                 ->filter(function (Relation $relation) { return $relation instanceof ProvidesTable; })
                 ->map(function (Relation $relation) use ($tabs) {

                     $card = sv_loader($relation->indexRoute($this->entry));

                     return $tabs->addTab(sv_tab($relation->getName(), $card));
                 });

        $page = Page::make('Edit '.$resource->getEntryLabel($this->entry));
        $page->addBlock($tabs);

        return $page->build();
    }
}