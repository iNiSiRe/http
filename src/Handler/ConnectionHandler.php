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

        $connection->once('data', array($this, 'handleData'));
    }

    public function handleData($data)
    {
        if (strlen($data) > $this->maxSize) {
            $this->emit('error', array(new \OverflowException("Maximum header size of {$this->maxSize} exceeded."), $this));

            return;
        }

        $requestParser = new RequestParser();
        $request = $requestParser->parse($data);
        $this->emit('request', array($request));
    }
}