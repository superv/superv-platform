<?php

namespace SuperV\Platform\Contracts;

interface Container extends \Illuminate\Contracts\Container\Container
{
    public static function getInstance();

    public function makeWith($abstract, array $parameters = []);
}
