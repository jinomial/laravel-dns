<?php

namespace Jinomial\LaravelDns;

use Closure;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Jinomial\LaravelDns\Contracts\Dns\Factory as FactoryContract;
use Jinomial\LaravelDns\Contracts\Dns\Socket;

class DnsManager implements FactoryContract
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The array of resolved sockets.
     */
    protected array $sockets = [];

    /**
     * The registered custom driver creators.
     */
    protected array $customCreators = [];

    /**
     * Create a new DNS manager instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a socket instance by name.
     */
    public function socket(?string $name = null): Socket
    {
        $name = $name ?: $this->getDefaultSocket();

        return $this->sockets[$name] = $this->get($name);
    }

    /**
     * Attempt to get the DNS socket from the local cache.
     */
    protected function get(string $name): Socket
    {
        return $this->sockets[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given DNS socket.
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): Socket
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("DNS socket [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($name, $config);
        } else {
            $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

            if (method_exists($this, $driverMethod)) {
                return $this->{$driverMethod}($name, $config);
            } else {
                throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
            }
        }
    }

    /**
     * Call a custom driver creator.
     *
     * @return mixed
     */
    protected function callCustomCreator(string $name, array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $name, $config);
    }

    /**
     * Create an instance of the DNS over HTTPS driver.
     *
     * @api
     */
    protected function createDohDriver(string $name, array $config): Socket
    {
        return new Sockets\DnsOverHttps(
            $name,
            $this->guzzle($config),
            $config['endpoint'] ?? null
        );
    }

    /**
     * Create an instance of the DNS over HTTPS driver.
     *
     * @api
     * @psalm-suppress PossiblyUnusedParam
     */
    protected function createSystemDriver(string $name, array $config): Socket
    {
        return new Sockets\DnsResolver($name);
    }

    /**
     * Get a fresh Guzzle HTTP client instance.
     */
    protected function guzzle(array $config): HttpClient
    {
        return new HttpClient(Arr::add(
            $config['guzzle'] ?? [],
            'connect_timeout',
            60
        ));
    }

    /**
     * Get the DNS socket configuration.
     */
    protected function getConfig(string $name): ?array
    {
        return Config::get("dns.sockets.{$name}");
    }

    /**
     * Get the default DNS socket name.
     */
    public function getDefaultSocket(): string
    {
        return Config::get('dns.default');
    }

    /**
     * Set the default DNS socket name.
     *
     * @api
     */
    public function setDefaultSocket(string $name): void
    {
        Config::set('dns.default', $name);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @api
     */
    public function extend(string $driver, Closure $callback): DnsManager
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Disconnect the given socket and remove from local cache.
     *
     * @api
     */
    public function purge(?string $name = null): void
    {
        $name = $name ?: $this->getDefaultSocket();

        unset($this->sockets[$name]);
    }

    /**
     * Get the application instance used by the manager.
     *
     * @api
     */
    public function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @api
     */
    public function setApplication(Application $app): DnsManager
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Forget all of the resolved socket instances.
     *
     * @api
     */
    public function forgetSockets(): DnsManager
    {
        $this->sockets = [];

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @api
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->socket()->$method(...$parameters);
    }
}
