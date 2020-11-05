<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Yosymfony\ResourceWatcher\Crc32ContentHash;
use Yosymfony\ResourceWatcher\ResourceCacheMemory;
use Yosymfony\ResourceWatcher\ResourceWatcher;

class Serve extends Command
{
    protected $signature = 'serve';

    protected $description = 'Start simple web server for development';

    protected int $portOffset = 0;

    protected ResourceWatcher $watcher;

    public function handle()
    {
        $this->line("<info>Starting development server:</info> http://{$this->host()}:{$this->port()}");

        $this->makeWatcher();

        $process = new Process($this->serverCommand());

        $process->start(function ($type, $data) {
            $this->output->write($data);
        });

        while ($process->isRunning()) {
            if ($this->watcher->findChanges()->hasChanges()) {
                $this->call('build');
            }
            usleep(0.5 * 1000000);
        }

        $status = $process->getExitCode();

        if ($status && $this->portOffset++ < 10) {
            $this->handle();
        }

        return $status;
    }

    protected function makeWatcher()
    {
        $this->watcher = new ResourceWatcher(
            new ResourceCacheMemory(),
            (new Finder())->files()->in(['app', 'posts', 'resources']),
            new Crc32ContentHash()
        );
        $this->watcher->initialize();
    }

    protected function serverCommand()
    {
        return [
            (new PhpExecutableFinder)->find(false),
            '-S',
            $this->host().':'.$this->port(),
            '-t',
            'public'
        ];
    }

    protected function host()
    {
        return '127.0.0.1';
    }

    protected function port()
    {
        return 1337 + $this->portOffset;
    }
}