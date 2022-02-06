<?php

namespace Wyxos\ErrorTracker;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class IssueTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'issue:test {--base=production}';

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

        return 0;
    }
}
