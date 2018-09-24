<?php

namespace SuperV\Platform\Console;

use Illuminate\Console\Command;
use SuperV\Platform\Domains\Auth\Account;

class SuperVInstallCommand extends Command
{
    protected $signature = 'superv:install';

    protected $description = 'Install SuperV Platform';

    public function handle()
    {
        $this->comment('Installing SuperV');
        $this->call('migrate', ['--scope' => 'platform', '--force' => true]);

        // Create default account
        Account::query()->create(['name' => 'default']);

        $this->setEnv('SV_INSTALLED=true');

        $this->comment("SuperV installed..! \n");
    }

    public function setEnv($line)
    {
        list($variable, $value) = explode('=', $line, 2);

        $data = $this->readEnvironmentFile();

        array_set($data, $variable, $value.PHP_EOL);

        $this->writeEnvironmentFile($data);
    }

    protected function readEnvironmentFile()
    {
        $data = [];

        $file = base_path('.env');

        if (! file_exists($file)) {
            return $data;
        }

        foreach (file($file) as $line) {
            // Check for # comments.
            if (starts_with($line, '#')) {
                $data[] = $line;
            } elseif ($operator = strpos($line, '=')) {
                $key = substr($line, 0, $operator);
                $value = substr($line, $operator + 1);

                $data[$key] = $value;
//                $data[$key] = trim($value);
            }
        }

        return $data;
    }

    protected function writeEnvironmentFile($data)
    {
        $contents = '';

        foreach ($data as $key => $value) {
            if ($key) {
                $contents .= strtoupper($key).'='.$value;
            } else {
                $contents .= $value.PHP_EOL;
            }
        }

        $file = base_path('.env');

        file_put_contents($file, $contents);
    }

    protected function setEnvOld()
    {
        $file = base_path('.env');
        $lines = file_exists($file) ? file($file) : [];

        $envParam = 'SV_INSTALLED';
        $done = false;
        foreach ($lines as &$line) {
//            $line = trim($line);
            if (starts_with($line, $envParam)) {
                $done = (bool)($line = "{$envParam}=true");
                break;
            }
        }

        if (! $done) {
            array_push($lines, "\r\n"."{$envParam}=true");
        }

        file_put_contents($file, implode($lines));
    }
}