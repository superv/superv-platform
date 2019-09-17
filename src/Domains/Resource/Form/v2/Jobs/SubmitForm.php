<?php

namespace SuperV\Platform\Domains\Resource\Form\v2\Jobs;

use SuperV\Platform\Domains\Resource\Form\v2\Contracts\Form;

class SubmitForm
{
    public function handle(Form $form, $data)
    {
        foreach ($data as $key => $value) {
            $form->setFieldValue($key, $value);
        }
    }

    /**
     * @return static
     */
    public static function resolve()
    {
        return app(static::class);
    }
}
