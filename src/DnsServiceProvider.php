<?php

namespace Jinomial\LaravelDns;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Jinomial\LaravelDns\Commands\DnsQueryCommand;

/**
 * @api
 */
class DnsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/dns.php',
            'dns'
        );

        $this->app->singleton(DnsManager::class, function (Application $app) {
            return new DnsManager($app);
        });
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        // Publish configuration files.
        $this->publishes([
            __DIR__.'/../config/dns.php' => config_path('dns.php'),
        ], 'laravel-dns-config');

        // Register console commands.
        if ($this->app->runningInConsole()) {
            $this->commands([
                DnsQueryCommand::class,
            ]);
        }
    }
}
