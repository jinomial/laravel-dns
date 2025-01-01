<?php

namespace Jinomial\LaravelDns\Facades;

use Illuminate\Support\Facades\Facade;
use Jinomial\LaravelDns\DnsManager;

/**
 * @see \Jinomial\LaravelDns\DnsManager
 */
class Dns extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DnsManager::class;
    }
}
