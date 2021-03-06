<?php

namespace Tests\Platform\Domains\Database\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SuperV\Platform\Domains\Database\Migrations\Console\MigrateCommand;
use SuperV\Platform\Domains\Database\Migrations\Console\MigrateMakeCommand;
use SuperV\Platform\Domains\Database\Migrations\Console\RefreshCommand;
use SuperV\Platform\Domains\Database\Migrations\Console\ResetCommand;
use SuperV\Platform\Domains\Database\Migrations\Console\RollbackCommand;
use SuperV\Platform\Domains\Database\Migrations\DatabaseMigrationRepository;
use SuperV\Platform\Domains\Database\Migrations\MigrationCreator;
use SuperV\Platform\Domains\Database\Migrations\MigrationServiceProvider;
use SuperV\Platform\Domains\Database\Migrations\Migrator;
use Tests\Platform\TestCase;

class MigrationServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    function test__get_registered_with_platform()
    {
        $this->assertProviderRegistered(MigrationServiceProvider::class);
    }

    function test__extends_database_migration_repository()
    {
        $this->assertInstanceOf(\Illuminate\Database\MigrationServiceProvider::class, new MigrationServiceProvider($this->app));
        $this->assertInstanceOf(DatabaseMigrationRepository::class, $this->app['migration.repository']);
    }

    function test__extends_migrator()
    {
        $this->assertInstanceOf(Migrator::class, $this->app['migrator']);
    }

    function test__extends_migration_creator()
    {
        $this->assertInstanceOf(MigrationCreator::class, $this->app['migration.creator']);
    }

    function test__extends_migration_console_commands()
    {
        $this->assertInstanceOf(MigrateCommand::class, $this->app['command.migrate']);
        $this->assertInstanceOf(MigrateMakeCommand::class, $this->app['command.migrate.make']);
        $this->assertInstanceOf(RollbackCommand::class, $this->app['command.migrate.rollback']);
        $this->assertInstanceOf(RefreshCommand::class, $this->app['command.migrate.refresh']);
        $this->assertInstanceOf(ResetCommand::class, $this->app['command.migrate.reset']);
    }
}