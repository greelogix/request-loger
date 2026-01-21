<?php

namespace Gl\RequestLogger;

use Gl\RequestLogger\Console\InstallCommand;
use Illuminate\Support\ServiceProvider;

class RequestLoggerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/request-logger.php', 'request-logger');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/request-logger.php' => config_path('request-logger.php'),
        ], 'request-logger-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_request_logs_table.php' => database_path('migrations/'.date('Y_m_d_His').'_create_request_logs_table.php'),
        ], 'request-logger-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/request-logger'),
        ], 'request-logger-views');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'request-logger');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
