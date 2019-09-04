<?php

namespace SuperV\Platform\Testing;

use Current;
use Hub;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SuperV\Platform\Console\Jobs\InstallSuperV;
use SuperV\Platform\Domains\Addon\Addon;
use SuperV\Platform\Domains\Addon\AddonModel;
use SuperV\Platform\Domains\Addon\Installer;
use SuperV\Platform\Domains\Addon\Locator;
use SuperV\Platform\Domains\Port\Port;
use SuperV\Platform\Domains\Port\PortDetectedEvent;
use SuperV\Platform\PlatformServiceProvider;
use Tests\Platform\ComposerLoader;

class PlatformTestCase extends OrchestraTestCase
{
    use TestHelpers;

    /**
     * Temporary directory to be created in storage folder
     * during setup and deleted in tearDown
     *
     * @var string
     */
    protected $tmpDirectory;

    protected $packageProviders = [];

    protected $appConfig = [
        'app.key' => 'base64:SkW/b3Bg7pb2vvIOad6noSrFSR7eUS8ZdCXl0LoRQNI=',
    ];

    protected $shouldInstallPlatform = true;

    protected $shouldBootPlatform = false;

    protected $installs = [];

    protected $handleExceptions = true;

    protected $basePath;

    public function basePath($path = null)
    {
        return __DIR__.($path ? '/'.$path : '');
    }

    protected function getPackageProviders($app)
    {
        return array_flatten(array_merge([PlatformServiceProvider::class], $this->packageProviders));
    }

    protected function getEnvironmentSetUp($app)
    {
        if (! empty($this->appConfig)) {
            $app['config']->set($this->appConfig);
        }

        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUp()
    {
        parent::setUp();

        \Mockery::getConfiguration()->allowMockingNonExistentMethods(false);

        if ($this->handleExceptions === false) {
            $this->withoutExceptionHandling();
        }

        $this->loadLaravelMigrations();

        $this->makeTmpDirectory();

        $this->setUpMacros();

        if ($this->shouldInstallPlatform()) {
            $this->installSuperV();
        }
    }

    protected function tearDown()
    {
        if ($this->tmpDirectory) {
            app('files')->deleteDirectory(__DIR__.'/../tmp');
        }

        parent::tearDown();
    }

    protected function makeTmpDirectory(): void
    {
        if ($this->tmpDirectory) {
            $this->tmpDirectory = __DIR__.'/../tmp/'.$this->tmpDirectory;
            if (! file_exists($this->tmpDirectory)) {
                app('files')->makeDirectory($this->tmpDirectory, 0755, true);
            }
        }
    }

    protected function setUpAddon($slug = null, $path = null, $seed = false): Addon
    {
        $path = $path ?? 'tests/Platform/__fixtures__/sample-addon';
        $slug = $slug ?? 'superv.addons.sample';

        ComposerLoader::load(base_path($path));
        $installer = $this->app->make(Installer::class)
                               ->setNamespace($slug)
                               ->setPath($path);
        $installer->install();
        if ($seed === true) {
            $installer->seed();
        }

        $entry = AddonModel::byNamespace($slug);

        return $entry->resolveAddon();
    }

    protected function setUpPorts()
    {
        Hub::register(new class extends Port
        {
            protected $slug = 'web';

            protected $hostname = 'superv.io';

            protected $theme = 'themes.starter';
        });

        Hub::register(new class extends Port
        {
            protected $slug = 'acp';

            protected $hostname = 'superv.io';

            protected $prefix = 'acp';
        });

        Hub::register(new class extends Port
        {
            protected $slug = 'api';

            protected $hostname = 'api.superv.io';
        });
    }

    /**
     * Setup and Activate a test port
     *
     * @param      $hostname
     * @param null $prefix
     * @return \SuperV\Platform\Domains\Port\Port
     */
    protected function setUpCustomPort($hostname, $prefix = null)
    {
        $port = $this->setUpPort(['slug' => 'api', 'hostname' => $hostname, 'prefix' => $prefix]);
        PortDetectedEvent::dispatch($port);

        return $port;
    }

    protected function makeRequest($path = null)
    {
        $this->app->extend('request', function () use ($path) {
            return Request::create('http://'.Current::port()->root().($path ? '/'.$path : ''));
        });
    }

    protected function makeUploadedFile($filename = 'square.png')
    {
        return new UploadedFile($this->basePath('__fixtures__/'.$filename), $filename);
    }

    protected function installAddons(): void
    {
        $this->app->setBasePath($this->basePath ?? realpath(__DIR__.'/../../../../../'));

        foreach ($this->installs as $addon) {
            Installer::resolve()
                     ->setLocator(new Locator())
                     ->setNamespace($addon)
                     ->install();
        }
    }

    protected function setConfigParams(): void
    {
        config([
            'superv.installed' => true,
            'jwt.secret'       => 'skdjfslkdfj',
        ]);
    }

    protected function installSuperV(): void
    {
        InstallSuperV::dispatch();

        $this->setConfigParams();

        $this->handlePostInstallCallbacks();

        if ($this->shouldBootPlatform) {
            (new PlatformServiceProvider($this->app))->boot();
        }

        $this->installAddons();
    }

    protected function shouldInstallPlatform()
    {
        return $this->shouldInstallPlatform && method_exists($this, 'refreshDatabase');
    }
}
