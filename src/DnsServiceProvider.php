<?php

namespace Jinomial\LaravelDns;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Jinomial\LaravelDns\Commands\DnsQueryCommand;

class DnsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/dns.php',
            'dns'
        );

        $this->app->singleton('dns', function ($app) {
            return new DnsManager($app);
        });
    }

    /**
     * Bootstrap package services.
     */
    public function boot()
    {
        // Publish configuration files.
        $this->publishes([
            __DIR__.'/../config/dns.php' => config_path('dns.php'),
        ]);

        // Register console commands.
        if ($this->app->runningInConsole()) {
            $this->commands([
                DnsQueryCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'dns',
        ];
    }
}
