<?php

return [
    'websocket' => [
        'host' => env('RATCHET_WEBSOCKET_HOST', 'tcp://localhost:5555'),
    ],
    'zmq' => [
        'server_binding' => env('RATCHET_ZMQ_SERVER_BINDING', 'tcp://127.0.0.1:5555'),
        'host' => env('RATCHET_ZMQ_HOST', '0.0.0.0'),
        'port' => env('RATCHET_ZMQ_PORT', 8882),
    ],
];