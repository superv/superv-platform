<?php

namespace SuperV\Platform\Domains\Resource;

use Faker\Generator;
use SuperV\Platform\Domains\Resource\Field\Types\FieldType;

class Fake
{
    /**
     * @var \SuperV\Platform\Domains\Resource\Resource
     */
    protected $resource;

    /**
     * @var array
     */
    protected $overrides;

    /** @var \Faker\Generator */
    protected $faker;

    public function __construct(Resource $resource, array $overrides = [])
    {
        $this->resource = $resource;
        $this->overrides = $overrides;
    }

    public function __invoke()
    {
        $this->faker = app(Generator::class);

        return $this->resource->create(
            $this->resource->getFields()->map(function (FieldType $field) {
                if ($field->show() && $field->hasColumn()) {
                    return [$field->getColumnName(), $this->fake($field)];
                }
            })->filter()->toAssoc()->all()
        );
    }

    protected function fake(FieldType $field)
    {
        if ($value = array_get($this->overrides, $field->getColumnName())) {
            return $value;
        }
        if (method_exists($this, $method = camel_case('fake_'.$field->getType()))) {
            return $this->$method($field);
        }

        return $this->faker->text;
    }

    protected function fakeText(FieldType $field)
    {
        return $field->getName() === 'name' ? $this->faker->name : $this->faker->text;
    }

    protected function fakeTextarea(FieldType $field)
    {
        return $this->faker->text;
    }

    protected function fakeNumber(FieldType $field)
    {
        if ($field->getConfigValue('type') === 'decimal') {
            $max = $field->getConfigValue('total', 5) - 2;

            return $this->faker->randomFloat($field->getConfigValue('places', 2), 0, $max);
        }

        return $field->getName() === 'age' ? $this->faker->numberBetween(10, 99) : $this->faker->randomNumber();
    }

    protected function fakeEmail(FieldType $field)
    {
        return $this->faker->safeEmail;
    }

    protected function fakeBoolean(FieldType $field)
    {
        return $this->faker->boolean;
    }

    protected function fakeDatetime(FieldType $field)
    {
        return $field->getConfigValue('time') ? $this->faker->dateTime : $this->faker->date();
    }

    public static function create(Resource $resource, array $overrides = [])
    {
        return (new static($resource, $overrides))();
    }
}