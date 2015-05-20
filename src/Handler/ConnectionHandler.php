<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 20.05.15
 * Time: 17:00
 */

namespace React\Http\Handler;


use Evenement\EventEmitter;
use React\Http\RequestParser;
use React\Socket\ConnectionInterface;

class ConnectionHandler extends EventEmitter
{
    private $maxSize = 4096;

    public function handle(ConnectionInterface $connection)
    {
        // TODO: http 1.1 keep-alive
        // TODO: chunked transfer encoding (also for outgoing data)
        // TODO: multipart parsing

        $connection->once('data', function ($data) use ($connection) {
            $request = $this->createRequest($data);

            $connection->on('data', function ($data) use ($request) {
                $request->emit('data', array($data));
            });

            $this->emit('request', array($request));
        });
    }

    /**
     * @param $data
     *
     * @return \React\Http\Request
     */
    public function createRequest($data)
    {
        if (strlen($data) > $this->maxSize) {
            $this->emit('error', array(new \OverflowException("Maximum header size of {$this->maxSize} exceeded."), $this));

            return false;
        }

        $requestParser = new RequestParser();

        return $requestParser->parse($data);
    }
}