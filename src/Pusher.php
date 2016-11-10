<?php

namespace Inoplate\Velatchet;

use Crypt;
use SplObjectStorage;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Illuminate\Session\SessionManager;
use Illuminate\Foundation\Application;

class Pusher implements WampServerInterface
{
    protected $topics = [];

    protected $clients;

    protected $topicHandlers;

    protected $app;

    public function __construct(Application $app, TopicHandlers $topicHandlers)
    {
        $this->clients = new SplObjectStorage;
        $this->topicHandlers = $topicHandlers;
        $this->app = $app;
    }

    public function onSubscribe(ConnectionInterface $conn, $topic) 
    {
        $topic->autoDelete = true;
        $this->topics[$topic->getId()] = $topic;
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic) {}

    public function onOpen(ConnectionInterface $conn) 
    {
        $this->clients->attach($conn);
        $session = (new SessionManager($this->app))->driver();
        $cookies = $conn->WebSocket->request->getCookies();

        if(isset($cookies[config('session.cookie')])) {
            $laravelCookie = urldecode($cookies[config('session.cookie')]);
            $idSession = Crypt::decrypt($laravelCookie);
        }else {
            $idSession = null;
        }

        $session->setId($idSession);
        $conn->session = $session;
    }

    public function onClose(ConnectionInterface $conn) {}

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) 
    {
        // In this application if clients send data it's because the user hacked around in console
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) 
    {
        // In this application if clients send data it's because the user hacked around in console
        $conn->close();
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {}

    public function onMessagePushed($payload)
    {
        $payload = json_decode($payload, true);
        $topics = $payload['topics'];
        $clients = $this->clients;

        unset($payload['topics']);

        foreach ($topics as $topic) {
            // No registered topic handler, so nothing to return
            if (!$handler = $this->topicHandlers->getHandler($topic)) {
                return;
            }
            
            // Look up for topic user subscribed to
            if (!array_key_exists($topic, $this->topics)) {
                return;
            }

            $topic = $this->topics[$topic];

            call_user_func_array($handler, compact('topic', 'clients', 'payload'));
        }
    }
}