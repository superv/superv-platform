<?php

namespace Tests\Platform\Domains\Resource\Http\Controllers;

use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Resource\ResourceFactory;
use SuperV\Platform\Testing\HelperComponent;

trait ResponseHelper
{
    public function getResourceView(EntryContract $entry): HelperComponent
    {
        $resource = ResourceFactory::make($entry);
        $response = $this->getJsonUser($resource->route('entry.view', $entry));

        return HelperComponent::from($response->decodeResponseJson('data'));

        $page = HelperComponent::from($response->decodeResponseJson('data'));

        $view = HelperComponent::from($page->getProp('blocks.0'));

        return $view;
    }
}
