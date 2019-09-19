<?php

namespace SuperV\Platform\Domains\Database\Migrations\Console;

use SuperV\Platform\Domains\Database\Migrations\Scopes;

class MigrateMakeCommand extends \Illuminate\Database\Console\Migrations\MigrateMakeCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:migration {name : The name of the migration.}
        {--create= : The table to be created.}
        {--table= : The table to migrate.}
        {--path= : The location where the migration file should be created.}
        {--namespace= : The namespace of the migration.}
        ';

    /** @var \SuperV\Platform\Domains\Database\Migrations\MigrationCreator */
    protected $creator;

    public function handle()
    {
        parent::handle();
    }

    protected function getMigrationPath()
    {
        if ($this->option('namespace')) {
            $path = Scopes::path($this->option('namespace'));
        }

        $path = $path ?? parent::getMigrationPath();

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        return $path;

    }
}
