<?php

namespace Wyxos\ErrorTracker;

use Illuminate\Console\Command;

class IssueTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'issue:test';

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
       $exception = new \Exception('This is a sample exception.');

       ErrorTracker::instance()->capture($exception);

       return 0;
    }
}