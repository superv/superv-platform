<?php

namespace SuperV\Platform\Domains\Resource\Database;

use SuperV\Platform\Domains\Resource\ResourceConfig;
use SuperV\Platform\Domains\Resource\ResourceModel;

class ResourceRepository
{
    /**
     * @var \SuperV\Platform\Domains\Resource\ResourceModel
     */
    protected $model;

    public function __construct(ResourceModel $model)
    {
        $this->model = $model;
    }

    public function create(ResourceConfig $config)
    {
        $attributes = [
            'uuid'       => uuid(),
            'name'       => $config->getName(),
            'namespace'  => $config->getNamespace(),
            'identifier' => $config->getNamespace().'.'.$config->getName(),
            'dsn'        => $config->getDriver()->toDsn(),
            'model'      => $config->getModel(),
            'config'     => $config->toArray(),
            'restorable' => (bool)$config->isRestorable(),
            'sortable'   => (bool)$config->isSortable(),
        ];

        return $this->model->newQuery()->create($attributes);
    }
}
