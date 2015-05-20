<?php

namespace React\Http;

use Evenement\EventEmitter;
use React\Http\Handler\ConnectionHandler;
use React\Http\Handler\RequestHandler;
use React\Socket\ServerInterface as SocketServerInterface;
use React\Socket\ConnectionInterface;

/** @event request */
class Server extends EventEmitter implements ServerInterface
{
    private $io;

    public function __construct(SocketServerInterface $io)
    {
        $this->io = $io;

        $connectionHandler = new ConnectionHandler();
        $requestHandler = new RequestHandler();
        $this->io->on('connection', [$connectionHandler, 'handle']);

        $connectionHandler->on('request', );

    }

    public function emitRequest(ConnectionInterface $connection, Request $request)
    {
        $response = new Response($connection);
        $response->on('close', array($request, 'close'));

        if (!$this->listeners('request')) {
            $response->end();

            return;
        }

        $this->emit('request', array($request, $response));
    }

    public function emitRequestData(Request $request, $data)
    {
        $request->emit('data', array($data));
    }
}
