<?php

namespace Jinomial\LaravelDns\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jinomial\LaravelDns\LaravelDns
 */
class Dns extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dns';
    }
}
