<?php

namespace SuperV\Platform\Extensions;

use SuperV\Platform\Domains\Resource\Action\DeleteEntryAction;
use SuperV\Platform\Domains\Resource\Extension\Contracts\ExtendsResource;
use SuperV\Platform\Domains\Resource\Resource;
use SuperV\Platform\Domains\Resource\Table\ResourceTable;
use SuperV\Platform\Domains\UI\Page\ResourcePage;

class ResourceActivityExtension implements ExtendsResource
{
    /** @var Resource */
    protected $resource;

    public function extend(Resource $resource)
    {
        $resource->onIndexPage(function (ResourcePage $page) {
            $page->setActions([]);
        });

        $resource->onIndexConfig(function (ResourceTable $table) {
            $table->addRowAction(DeleteEntryAction::class);
            $table->orderByLatest();
        });
        $fields = $resource->indexFields();
        $fields->show('entry');
        $fields->show('user')->copyToFilters();
        $fields->show('resource')->copyToFilters();

        $resource->searchable(['email']);
    }

    public function extends(): string
    {
        return 'sv_activities';
    }



}
