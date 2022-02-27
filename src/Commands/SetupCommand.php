<?php

namespace Wyxos\ErrorTracker\Commands;

use Illuminate\Console\Command;
use function config;

class SetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'error-tracker:setup {token}';

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
        if (config('error-tracker.api_token')) {
            $this->error('API is already configured.');
            return 0;
        }

        $env = file_get_contents(base_path('.env'));

        $env .= "\n";
        $token = $this->argument('token');
        $env .= "ERROR_TRACKER_TOKEN=$token";

        file_put_contents(base_path('.env'), $env);

        return 0;
    }
}
