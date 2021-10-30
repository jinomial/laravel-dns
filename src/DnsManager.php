<?php

namespace Jinomial\LaravelDns;

use Closure;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Jinomial\LaravelDns\Contracts\Dns\Factory as FactoryContract;

class DnsManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved sockets.
     *
     * @var array
     */
    protected $sockets = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new DNS manager instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a socket instance by name.
     *
     * @param  string|null  $name
     * @return \Jinomial\LaravelDns\Contracts\Dns\Socket
     */
    public function socket($name = null)
    {
        $name = $name ?: $this->getDefaultSocket();

        return $this->sockets[$name] = $this->get($name);
    }

    /**
     * Attempt to get the DNS socket from the local cache.
     *
     * @param  string  $name
     * @return \Jinomial\LaravelDns\Contracts\Dns\Socket
     */
    protected function get($name)
    {
        return $this->sockets[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given DNS socket.
     *
     * @param  string  $name
     * @return \Jinomial\LaravelDns\Contracts\Dns\Socket
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
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
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator($name, array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $name, $config);
    }

    /**
     * Create an instance of the DNS over HTTPS driver.
     *
     * @param  string $name
     * @param  array  $config
     * @return \Jinomial\LaravelDns\Contracts\Dns\Socket
     */
    protected function createDohDriver($name, array $config)
    {
        return new Sockets\DnsOverHttps(
            $name,
            $this->guzzle($config),
            $config['endpoint'] ?? null
        );
    }

    /**
     * Get a fresh Guzzle HTTP client instance.
     *
     * @param  array  $config
     * @return \GuzzleHttp\Client
     */
    protected function guzzle(array $config)
    {
        return new HttpClient(Arr::add(
            $config['guzzle'] ?? [],
            'connect_timeout',
            60
        ));
    }

    /**
     * Get the DNS socket configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig(string $name)
    {
        return $this->app['config']["dns.sockets.{$name}"];
    }

    /**
     * Get the default DNS socket name.
     *
     * @return string
     */
    public function getDefaultSocket()
    {
        return $this->app['config']['dns.default'];
    }

    /**
     * Set the default DNS socket name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultSocket(string $name)
    {
        $this->app['config']['dns.default'] = $name;
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Disconnect the given socket and remove from local cache.
     *
     * @param  string|null  $name
     * @return void
     */
    public function purge($name = null)
    {
        $name = $name ?: $this->getDefaultSocket();

        unset($this->sockets[$name]);
    }

    /**
     * Get the application instance used by the manager.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return $this
     */
    public function setApplication($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Forget all of the resolved socket instances.
     *
     * @return $this
     */
    public function forgetSockets()
    {
        $this->sockets = [];

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->socket()->$method(...$parameters);
    }
}
