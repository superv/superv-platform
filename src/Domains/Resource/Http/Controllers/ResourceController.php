<?php

namespace SuperV\Platform\Domains\Resource\Http\Controllers;

use SuperV\Platform\Domains\Resource\Http\ResolvesResource;
use SuperV\Platform\Http\Controllers\BaseApiController;

class ResourceController extends BaseApiController
{
    use ResolvesResource;

    public function delete()
    {
        $resource = $this->resolveResource();

        $this->entry->delete();

        $message = __(':Entry was deleted', ['Entry' => $resource->getEntryLabel($this->entry)]);

        return ['data' => ['message' => $message]];
    }

    public function restore()
    {
        $resource = $this->resolveResource(false);

        /** @var    \SuperV\Platform\Domains\Resource\Database\Entry\ResourceEntry $entry */
        $entry = $resource->newQuery()->withTrashed()->find(request()->route()->parameter('id'));

        $entry->restore();
    }
}
