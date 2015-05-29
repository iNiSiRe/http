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

/**
 * Class MultipartDataProcessor
 *
 * @event form.file
 * @event form.field
 *
 * @package React\Http\Processor
 */
class MultipartDataProcessor extends AbstractProcessor
{
    const STATE_READY = 1;
    const STATE_FILE_DATA = 2;
    const STATE_END_LISTEN_STREAM = 3;

    const STATE_BLOCK_BEGIN = 1;
    const STATE_BLOCK_HEADER = 2;
    const STATE_FIELD_DATA = 3;

    private $state = self::STATE_BLOCK_BEGIN;

    /**
     * Internal event-emitter
     *
     * @var EventEmitter
     */
    private $events;

    public function __construct(Request $request)
    {
        $this->events = new EventEmitter();

        // Register internal listeners
        $this->events->on('data', [$this, 'parseData']);

        $this->boundary = $this->parseBoundary($request->headers->get('Content-Type'));
    }

    public function process($data)
    {
        $this->events->emit('data', [$data]);
    }

    protected function parseBoundary($header)
    {
        preg_match('#boundary=(.*)$#', $header, $matches);

        return $matches[1];
    }

    protected function parseHeaders($rawHeaders)
    {
        $headers = array();

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

    /**
     * Base data processor
     *
     * @param $data
     *
     * @throws \Exception
     */
    protected function parseData($data)
    {
        $parseDone = false;
        $offset = 0;

        while (!$parseDone && $offset < strlen($data)) {

            $delimiter = sprintf('--%s', $this->boundary);

            if (false === $position = strpos($data, $delimiter, $offset)) {
                throw new \Exception('Bad multipart request');
            }

            $offset = strlen($delimiter) + $position;

            if ($offset === strpos($data, '--', $offset)) {
                $this->emit('end');
                break;
            }

            $delimiter = "\r\n\r\n";

            if (false === $position = strpos($data, $delimiter, $offset)) {
                throw new \Exception('Bad multipart headers');
            }

            $rawHeaders = substr($data, $offset, $position - $offset);
            $headers = $this->parseHeaders($rawHeaders);

            $offset = $position + strlen($delimiter);

            switch (true) {
                case preg_match('/^form-data; name=\"(.*)\"; filename=\"(.*)\"$/', $headers->get('Content-Disposition'), $matches):

                    $delimiter = sprintf('%s--%s', "\r\n", $this->boundary);

                    $field = new FormField($matches[1]);

                    if (false === $position = strpos($data, $delimiter, $offset)) {
                        $this->state = self::STATE_FILE_DATA;
                        $data = substr($data, $offset);
                        $parseDone = true;
                    } else {
                        $data = substr($data, $offset, $position - $offset);
                    }

                    $this->emit('form.file', [$field, $matches[2]]);
                    $field->emit('data', [$data]);

                    if (false === $position) {
                        $this->events->removeAllListeners('data');
                        $this->events->on('data', function ($data) use ($field) {
                            $this->processFileData($field, $data);
                        });
                    }

                    $offset = $position;

                    break;

                case preg_match('/^form-data; name=\"(.*)\"$/', $headers->get('Content-Disposition'), $matches):

                    $delimiter = sprintf('%s--%s', "\r\n", $this->boundary);

                    if (false === $position = strpos($data, $delimiter, $offset)) {
                        $this->state = self::STATE_FIELD_DATA;
                        $body = substr($data, $offset);
                        $parseDone = true;
                    } else {
                        $body = substr($data, $offset, $position - $offset);
                    }

                    $field = new FormField($matches[1]);
                    $this->emit('form.field', [$field]);
                    $field->emit('data', [$body]);

                    $offset = $position;

                    break;
            }
        }
    }

    /**
     * @param FormField $field
     * @param           $data
     *
     * @throws \Exception
     */
    private function processFileData(FormField $field, $data)
    {
        $delimiter = sprintf('%s--%s', "\r\n", $this->boundary);

        if (false === $position = strpos($data, $delimiter)) {
            $fileData = $data;
        } else {
            $fileData = substr($data, 0, $position);
        }

        $field->emit('data', [$fileData]);

        if (false !== $position) {

            $this->events->removeAllListeners('data');
            $this->events->on('data', [$this, 'parseData']);

            $this->state = self::STATE_BLOCK_BEGIN;
            $this->parseData(substr($data, $position, -1));
        }
    }

}