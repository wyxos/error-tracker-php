<?php

namespace Wyxos\ErrorTracker\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Wyxos\ErrorTracker\ErrorTracker;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'error-tracker:test {--base=production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test against error tracker service.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $exception = new Exception('This is a sample exception.');

        $env = Str::upper($this->option('base'));

        $base = $env . '_URL';

        ErrorTracker::instance()
            ->setBaseUrl(constant("\\Wyxos\\ErrorTracker\\ErrorTracker::$base"))
            ->capture($exception);

        $this->info('Test error sent successfully.');

        return 0;
    }
}
