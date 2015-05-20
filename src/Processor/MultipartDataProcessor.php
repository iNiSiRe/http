<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 20.05.15
 * Time: 23:41
 */

namespace React\Http\Processor;

use Evenement\EventEmitter;
use React\Http\Foundation\HeaderDictionary;
use React\Http\Request;

class MultipartDataProcessor extends EventEmitter
{
    const STATE_READY = 1;
    const STATE_LISTEN_STREAM = 2;

    private $state = self::STATE_READY;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->boundary = $this->parseBoundary($request->headers->get('Content-Type'));
    }

    public function process($data)
    {
        $this->parseData($this->boundary, $data);
    }

    protected function parseBoundary($header)
    {
        preg_match('#boundary=(.*)$#', $header, $matches);

        return $matches[1];
    }

    protected function parseBlock($string)
    {
        if (strpos($string, 'filename') !== false) {
            $this->uploadFile($string);
            return;
        }

        // This may never be called, if an octet stream
        // has a filename it is catched by the previous
        // condition already.
//        if (strpos($string, 'application/octet-stream') !== false) {
//            $this->octetStream($string);
//            return;
//        }

        $this->parseRequestParameter($string);
    }

    protected function uploadFile($data)
    {

    }

    protected function parseRequestParameter($data)
    {

    }

    protected function parseHeaders($rawHeaders)
    {
        $headers = array();;

        foreach (explode("\n", $rawHeaders) as $i => $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                if(!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim($h[1]);
                } else if(is_array($headers[$h[0]])) {
                    $tmp = array_merge($headers[$h[0]],array(trim($h[1])));
                    $headers[$h[0]] = $tmp;
                } else {
                    $tmp = array_merge(array($headers[$h[0]]),array(trim($h[1])));
                    $headers[$h[0]] = $tmp;
                }
            }
        }

        return new HeaderDictionary($headers);
    }

    protected function parseData($boundary, $data)
    {
        $count = 0;
        $data = preg_replace("/--$boundary--\r\n/", '', $data, -1, $count);

        $isEnd = ($count == 1);

        // split content by boundary and get rid of last -- element
        $blocks = preg_split("#--$boundary\r\n#", $data);

        // loop data blocks
        foreach ($blocks as $block)
        {
            if (empty($block)) {
                continue;
            }

            if (!preg_match("/^(.*)\r\n\r\n(.*)$/", $block, $matches)) {
                continue;
            }

            $headers = $this->parseHeaders($matches[1]);
            $body = $matches[2];

            switch (true) {
                case preg_match('/^form-data; name=\"(.*)\"$/', $headers->get('Content-Disposition'), $matches):

                    $this->request->attributes->set($matches[1], $body);

                    break;
                case preg_match('/^form-data; name=\"(.*)\"; filename=\"(.*)\"$/', $headers->get('Content-Disposition'), $matches):

                    $this->state = self::STATE_LISTEN_STREAM;

                    break;
            }
        }

        if ($isEnd) {
            $this->emit('end');
        }
    }
}