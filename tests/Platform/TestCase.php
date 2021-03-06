<?php

namespace Tests\Platform;

use SuperV\Platform\Domains\Addon\Installer;
use SuperV\Platform\Domains\Addon\Locator;
use SuperV\Platform\Testing\PlatformTestCase;

abstract class TestCase extends PlatformTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setBasePath();
    }

    protected function installAddons(): void
    {
        foreach ($this->installs as $addon) {
            app(Installer::class)
                ->setLocator(new Locator(realpath(__DIR__.'/../../../../')))
                ->setSlug($addon)
                ->install();
        }
    }

    public function basePath($path = null)
    {
        return __DIR__.($path ? '/'.$path : '');
    }

    protected function setBasePath(): void
    {
        $basePath = realpath(__DIR__.'/../../');

        $this->app->setBasePath($basePath);
    }
}