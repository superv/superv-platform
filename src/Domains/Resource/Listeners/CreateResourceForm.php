<?php

namespace SuperV\Platform\Domains\Resource\Listeners;

use SuperV\Platform\Domains\Database\Events\TableCreatedEvent;
use SuperV\Platform\Domains\Resource\Form\FormRepository;

class CreateResourceForm
{
    /** @var string */
    protected $table;

    /** @var \SuperV\Platform\Domains\Resource\Form\FormModel */
    protected $formEntry;

    public function handle(TableCreatedEvent $event)
    {
        $table = $event->table;
        if (starts_with($table, 'sv_')) {
            return;
        }

        FormRepository::createForResource($event->config->getIdentifier());
    }
}
