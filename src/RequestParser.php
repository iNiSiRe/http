<?php

namespace React\Http;

use Evenement\EventEmitter;
use Guzzle\Parser\Message\MessageParser;

/**
 * @event headers
 * @event error
 */
class RequestParser extends EventEmitter
{
    /**
     * @param $data
     *
     * @return array
     */
    public function parse($data)
    {
        $parser = new MessageParser();
        $parsed = $parser->parseRequest($data);

        $parsedQuery = array();
        if ($parsed['request_url']['query']) {
            parse_str($parsed['request_url']['query'], $parsedQuery);
        }

        $request = new Request(
            $parsed['method'],
            $parsed['request_url']['path'],
            $parsedQuery,
            $parsed['version'],
            $parsed['headers']
        );

        return array($request, $parsed['body']);
    }
}
