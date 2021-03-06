<?php

namespace SuperV\Platform\Domains\Resource\Field\Types;

use Closure;
use SuperV\Platform\Domains\Resource\Field\Contracts\HasAccessor;
use SuperV\Platform\Domains\Resource\Field\Contracts\HasModifier;
use SuperV\Platform\Domains\Resource\Field\Contracts\RequiresDbColumn;
use SuperV\Platform\Domains\Resource\Field\Contracts\SortsQuery;
use SuperV\Platform\Domains\Resource\Field\FieldType;

class BooleanField extends FieldType implements RequiresDbColumn, HasAccessor, HasModifier, SortsQuery
{
    public function sortQuery($query, $direction)
    {
        $query->orderBy($this->field->getColumnName(), $direction);
    }

    public function getAccessor(): Closure
    {
        return function ($value) {
            return ($value === 'false' || ! $value) ? false : true;
        };
    }

    public function getModifier(): Closure
    {
        return function ($value) {
            return ($value === 'false' || ! $value) ? false : true;
        };
    }
}
