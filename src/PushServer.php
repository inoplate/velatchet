<?php

namespace Inoplate\Velatchet;

use ZMQ;
use React\EventLoop\Factory as ReactFactory;
use Ratchet\Server\IoServer;
use React\Socket\Server;
use React\ZMQ\Context;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use Inoplate\Velatchet\TopicHandlers;

class PushServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inoplate-velatchet:push-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run push server event loop';

    /**
     * @var Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @var Inoplate\Velatchet\TopicHandlers
     */
    protected $topicHandlers;

    /**
     * Create new PushServer instance
     * 
     * @param Application $app
     */
    public function __construct(Application $app, TopicHandlers $topicHandlers)
    {
        parent::__construct();

        $this->app = $app;
        $this->topicHandlers = $topicHandlers;
    }

    /**
     * Handle incoming console command
     * 
     * @return void
     */
    public function handle()
    {
        $this->info('Push notification started');        

        $loop   = ReactFactory::create();
        $pusher = new Pusher($this->app, $this->topicHandlers);
        $webServerBinding = config('inoplate.velatchet.zmq.server_binding');
        $webSocketHost = config('inoplate.velatchet.zmq.host');
        $webSocketPort = config('inoplate.velatchet.zmq.port');

        // Listen for the web server to make a ZeroMQ push after an ajax request
        $context = new Context($loop);
        $pull = $context->getSocket(ZMQ::SOCKET_PULL);
        $pull->bind($webServerBinding);
        $pull->on('message', array($pusher, 'onMessagePushed'));

        // Set up our WebSocket server for clients wanting real-time updates
        $webSock = new Server($loop);
        $webSock->listen($webSocketPort, $webSocketHost); // Binding to 0.0.0.0 means remotes can connect
        $webServer = new IoServer(
            new HttpServer(
                new WsServer(
                    new WampServer(
                        $pusher
                    )
                )
            ),
            $webSock
        );

        $loop->run();
    }
}