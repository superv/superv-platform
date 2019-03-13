<?php

namespace Tests\Platform\Providers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SuperV\Platform\Providers\TwigServiceProvider;
use Tests\Platform\TestCase;
use TwigBridge\ServiceProvider as TwigBridgeServiceProvider;

class TwigServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function is_registered_if_enabled_by_config()
    {
        config()->set('superv.twig.enabled', true);

        $this->assertProviderRegistered(TwigServiceProvider::class);
        $this->assertProviderRegistered(TwigBridgeServiceProvider::class);
    }
}