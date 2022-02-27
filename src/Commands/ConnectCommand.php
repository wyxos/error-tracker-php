<?php

namespace Wyxos\ErrorTracker\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Wyxos\ErrorTracker\ErrorTracker;

class ConnectCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'error-tracker:connect {--base=production}';

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
        $env = Str::upper($this->option('base'));

        $base = $env . '_URL';

        ErrorTracker::instance()
            ->setBaseUrl(constant("\\Wyxos\\ErrorTracker\\ErrorTracker::$base"))
            ->connect();

        $this->info('Project setup correctly.');

        return 0;
    }
}
