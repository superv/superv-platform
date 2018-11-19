<?php

namespace SuperV\Platform\Domains\Resource\Action;

use SuperV\Platform\Domains\Context\Negotiator;

class Builder
{
    /** @var \SuperV\Platform\Domains\Resource\Action\Contracts\ActionContract */
    protected $action;

    protected $contexts = [];

    public function __construct($actionClass)
    {
        $this->addContext($this->action = $actionClass::make());
    }

    public function compose(): array
    {
        Negotiator::deal($this->contexts);

        return $this->action->compose()->get();
    }

    public function addContext($context)
    {
        $this->contexts[] = $context;

        return $this;
    }
}