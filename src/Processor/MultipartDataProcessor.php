<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 20.05.15
 * Time: 23:41
 */

namespace React\Http\Processor;

use Evenement\EventEmitter;
use React\Http\Foundation\File;
use React\Http\Foundation\HeaderDictionary;
use React\Http\Request;

class MultipartDataProcessor extends EventEmitter
{
    const STATE_READY = 1;
    const STATE_LISTEN_STREAM = 2;
    const STATE_END_LISTEN_STREAM = 3;

    const STATE_BEGIN = 1;

    private $state = self::STATE_BEGIN;

    /**
     * @var File
     */
    private $file = null;

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

            if (preg_match("/^(.*)\r\n\r\n(.*)\r\n$/", $block, $matches)) {
                $headers = $this->parseHeaders($matches[1]);
                $body = $matches[2];
            } elseif (strpos($block, "\r\n\r\n") !== false) {
                list ($headers, $body) = explode("\r\n\r\n", $block);
                $headers = $this->parseHeaders($headers);
            } else {
                if (false !== $endFlagPosition = strpos($block, "\r\n")) {
                    $body = substr($block, 0, $endFlagPosition);
                    $this->state = self::STATE_END_LISTEN_STREAM;
                } else {
                    $body = $block;
                }
                $headers = new HeaderDictionary();
            }

            switch (true) {

                case ($this->state == self::STATE_LISTEN_STREAM && $this->file !== null):
                    $this->file->emit('data', [$body]);
                    break;

                case ($this->state == self::STATE_END_LISTEN_STREAM && $this->file !== null):
                    $this->file->emit('end', [$body]);
                    $this->file = null;
                    $this->state = self::STATE_READY;
                    break;

                case preg_match('/^form-data; name=\"(.*)\"; filename=\"(.*)\"$/', $headers->get('Content-Disposition'), $matches):

                    $this->state = self::STATE_LISTEN_STREAM;
                    $file = new File($matches[2], $headers->get('Content-Type'));
                    $this->request->emit('form.file', [$matches[1], $file]);
                    $file->emit('data', [$body]);
                    $this->file = $file;

                    break;

                case preg_match('/^form-data; name=\"(.*)\"$/', $headers->get('Content-Disposition'), $matches):

                    $this->request->emit('form.field', [$matches[1], $body]);

                    break;
            }
        }

        if ($isEnd) {
            $this->emit('end');
        }
    }

    protected function parseData2($boundary, $data)
    {
        switch (true) {

            case $this->state == self::STATE_BEGIN:

                $delimiter = sprintf('--%s--%s', $boundary, "\r\n");
                if (false === $offset = strpos($data, $delimiter)) {

                }

                break;
        }
    }

}