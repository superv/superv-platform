<?php

namespace SuperV\Platform\Domains\Resource\Field\Types;

use SuperV\Platform\Support\Composer\Payload;

class ModalFormField extends FieldType
{
    protected function boot()
    {
        $this->on('form.composing', $this->composer());
    }

    protected function composer()
    {
        return function (Payload $payload) {
            $payload->set('config.form', $this->getConfigValue('form'));
        };
    }
}