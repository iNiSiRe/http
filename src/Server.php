<?php

namespace React\Http;

use Evenement\EventEmitter;
use React\Http\Handler\ConnectionHandler;
use React\Http\Handler\RequestHandler;
use React\Socket\ServerInterface as SocketServerInterface;
use React\Socket\ConnectionInterface;

/**
 * @event request
 */
class Server extends EventEmitter implements ServerInterface
{
    private $io;

    public function __construct(SocketServerInterface $io)
    {
        // TODO: http 1.1 keep-alive
        // TODO: chunked transfer encoding (also for outgoing data)

        $this->io = $io;

        $this->io->on('connection', function (ConnectionInterface $connection) {
            $connectionHandler = new ConnectionHandler();

            // Handle connection
            $connectionHandler->handle($connection);

            // Handle request
            $connectionHandler->on('request', function (Request $request) use ($connection) {
                $this->emitRequest($connection, $request);
            });
        });
    }

    /**
     * @param ConnectionInterface $connection
     * @param Request             $request
     */
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
}
