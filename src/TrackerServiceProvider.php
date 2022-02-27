<?php

namespace Wyxos\ErrorTracker;

use Illuminate\Support\ServiceProvider;
use Wyxos\ErrorTracker\Commands\ConnectCommand;
use Wyxos\ErrorTracker\Commands\SetupCommand;
use Wyxos\ErrorTracker\Commands\TestCommand;

class TrackerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([TestCommand::class, ConnectCommand::class, SetupCommand::class]);
        }

        $this->publishes([
            __DIR__ . '/../config/error-tracker.php' => config_path('error-tracker.php')
        ]);

        $this->app->singleton('error-tracker', function () {
            return ErrorTracker::instance();
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/error-tracker.php', 'error-tracker');
    }
}
