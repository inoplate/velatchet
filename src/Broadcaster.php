<?php

namespace Inoplate\Velatchet;

use ZMQ;
use ZMQContext;
use Illuminate\Contracts\Broadcasting\Broadcaster as Contract;

class Broadcaster implements Contract
{
    /**
     * The Pusher SDK instance.
     *
     * @var Pusher
     */
    protected $pusher;

    /**
     * Web socket host
     * 
     * @var string
     */
    protected $host;

    /**
     * Create a new broadcaster instance.
     *
     * @param  Pusher  $pusher
     * @param  string  $host
     * @return void
     */
    public function __construct(Pusher $pusher, $host)
    {
        $this->pusher = $pusher;
        $this->host = $host;
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $context = new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect($this->host);

        $payload['topics'] = $channels;

        $socket->send(json_encode($payload));
    }

    /**
     * Get the Pusher SDK instance.
     *
     * @return Pusher
     */
    public function getPusher()
    {
        return $this->pusher;
    }
}
