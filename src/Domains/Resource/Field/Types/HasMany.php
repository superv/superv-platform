<?php

namespace SuperV\Platform\Domains\Resource\Field\Types;

class HasMany extends FieldType
{
    protected $hasColumn = false;

    public function show(): bool
    {
        return false;
    }
}