<?php

namespace Jinomial\LaravelDns\Sockets;

use Jinomial\LaravelDns\Contracts\Dns\Socket as SocketContract;

abstract class Socket implements SocketContract
{
    /**
     * The name that is configured for the socket.
     */
    protected string $name;
}
