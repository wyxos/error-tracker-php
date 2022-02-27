<?php

namespace Wyxos\ErrorTracker;

use Illuminate\Support\ServiceProvider;

class TrackerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([IssueTestCommand::class]);
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
