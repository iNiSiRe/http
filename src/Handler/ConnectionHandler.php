<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 20.05.15
 * Time: 17:00
 */

namespace React\Http\Handler;


use Evenement\EventEmitter;
use React\Http\Request;
use React\Http\RequestParser;
use React\Socket\ConnectionInterface;

class ConnectionHandler extends EventEmitter
{
    private $maxSize = 4096;

    public function handle(ConnectionInterface $connection)
    {
        $connection->once('data', function ($data, $c, $end) use ($connection) {
            $result = $this->createRequest($data);

            if (!$result) {
                return;
            }

            /**
             * @var Request $request
             * @var string  $body
             */
            list ($request, $body) = $result;

            $this->emit('request', array($request));

            $connection->on('data', function ($data, $connection, $end) use ($request) {
                $request->emit('data', array($data, $end));
            });

            $request->on('pause', function () use ($connection) {
                $connection->emit('pause');
            });
            $request->on('resume', function () use ($connection) {
                $connection->emit('resume');
            });

            $request->emit('data', [$body, $end]);
        });
    }

    /**
     * @param $data
     *
     * @return array
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