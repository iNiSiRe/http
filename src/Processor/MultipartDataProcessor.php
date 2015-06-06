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
 * @event   data
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
    const STATE_BOUNDARY_BEGIN = 4;
    const STATE_HEADER_BEGIN = 5;
    const STATE_BLOCK_END = 6;

    protected $buffer;

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

    public function process($data, $isEnd = false)
    {
        $this->events->emit('data', [$data, $isEnd]);
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
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim($h[1]);
                } else if (is_array($headers[$h[0]])) {
                    $tmp = array_merge($headers[$h[0]], array(trim($h[1])));
                    $headers[$h[0]] = $tmp;
                } else {
                    $tmp = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                    $headers[$h[0]] = $tmp;
                }
            }
        }

        return new HeaderDictionary($headers);
    }

    protected function validate($data, $needle, &$offset)
    {
        $valid = strpos($data, $needle, $offset) === $offset;

        if ($valid) {
            $offset += strlen($needle);
        }

        return $valid;
    }

    /**
     * @var FormField
     */
    protected $field = null;

    protected function getStateByHeaders(HeaderDictionary $headers)
    {
        switch (true) {
            case preg_match('/^form-data; name=\"(.*)\"; filename=\"(.*)\"$/', $headers->get('Content-Disposition'), $matches);
                $field = new FormField($matches[1]);
                $field->attributes->set('original_filename', $matches[2]);
                $field->setFile(true);
                $this->field = $field;
                $this->emit('data', [$field]);
                $state = self::STATE_FILE_DATA;
                break;

            case preg_match('/^form-data; name=\"(.*)\"$/', $headers->get('Content-Disposition'), $matches):
                $field = new FormField($matches[1]);
                $this->field = $field;
                $this->emit('data', [$field]);
                $state = self::STATE_FILE_DATA;
                break;

            default:
                throw new \Exception("Bad Content-Disposition header with content '{$headers->get('Content-Disposition')}'");
        }

        return $state;
    }

    /**
     * Base data processor
     *
     * @param      $data
     * @param bool $isEnd
     *
     * @throws \Exception
     */
    protected function parseData($data, $isEnd = false)
    {
        $parseDone = false;
        $offset = 0;

        while (!$parseDone && $offset < strlen($data)) {
            switch ($this->state) {
                case self::STATE_BLOCK_BEGIN:
                    if (!$this->validate($data, '--', $offset)) {
                        throw new \Exception('Bad multipart request');
                    }
                    $this->state = self::STATE_BOUNDARY_BEGIN;
                    break;

                case self::STATE_BOUNDARY_BEGIN:
                    if (!$this->validate($data, $this->boundary . "\r\n", $offset)) {
                        $this->buffer .= substr($data, $offset);
                        break;
                    }
                    $this->state = self::STATE_HEADER_BEGIN;
                    break;

                case self::STATE_HEADER_BEGIN:
                    $position = strpos($data, "\r\n\r\n", $offset);
                    if ($position === false) {
                        throw new \Exception('Bad multipart request');
                    }
                    $raw = substr($data, $offset, $position - $offset);
                    $headers = $this->parseHeaders($raw);
                    $offset = $position + 1;
                    $this->state = $this->getStateByHeaders($headers);
                    break;

                case self::STATE_FILE_DATA:
                    $endFlag = "\r\n--{$this->boundary}";
                    if (strlen($endFlag) + $offset > strlen($data)) {
                        $this->buffer .= substr($data, $offset);
                        break;
                    }
                    if ($this->field === null) {
                        throw new \Exception('Field not created');
                    }
                    $position = strpos($data, $endFlag, $offset);
                    if ($position === false) {
                        $this->field->emit('data', substr($data, $offset));
                    } else {
                        $this->field->emit('data', substr($data, $offset, $position - $offset));
                        $offset = $position + strlen($endFlag);
                        $this->state = self::STATE_BLOCK_END;
                    }
                    break;
                
                case self::STATE_BLOCK_END:
                    if ($this->validate($data, '--', $offset)) {
                        $this->emit('end');
                        $parseDone = true;
                    } else {
                        $this->state = self::STATE_HEADER_BEGIN;
                    }
                    break;
            }
        }

//        while (!$parseDone && $offset < strlen($data)) {
//
//
//            if (false === $position = strpos($data, $delimiter, $offset)) {
//                throw new \Exception('Bad multipart request');
//            }
//
//            $offset = strlen($delimiter) + $position;
//
//            if ($offset === strpos($data, '--', $offset)) {
//                $this->emit('end');
//                return;
//            }
//
//            $delimiter = "\r\n\r\n";
//
//            if (false === $position = strpos($data, $delimiter, $offset)) {
//
//                $buffer = substr($data, $offset);
//                $this->events->removeAllListeners('data');
//                $this->events->on('data', function ($data, $end) use ($buffer) {
//                    $this->parseData($buffer . $data, $end);
//                    $this->events->on('data', [$this, 'parseData']);
//                });
//
//                break;
//
//            } else {
//                $rawHeaders = substr($data, $offset, $position - $offset);
//            }
//
//            $headers = $this->parseHeaders($rawHeaders);
//
//            $offset = $position + strlen($delimiter);
//
//            switch (true) {
//
//                case preg_match('/^form-data; name=\"(.*)\"; filename=\"(.*)\"$/', $headers->get('Content-Disposition'), $matches):
//
//                    $delimiter = sprintf('%s--%s', "\r\n", $this->boundary);
//
//                    $field = new FormField($matches[1]);
//                    $field->attributes->set('original_filename', $matches[2]);
//                    $field->setFile(true);
//
//                    if (false === $position = strpos($data, $delimiter, $offset)) {
//                        $fileData = substr($data, $offset);
//                        $parseDone = true;
//                    } else {
//                        $fileData = substr($data, $offset, $position - $offset);
//                    }
//
//                    $this->emit('data', [$field]);
//
//                    if (!empty($fileData)) {
//                        $field->emit('data', [$fileData]);
//                    }
//
//                    if (false === $position) {
//                        $this->events->removeAllListeners('data');
//                        $this->events->on('data', function ($data, $isEnd) use ($field) {
//                            $this->processFileData($field, $data, $isEnd);
//                        });
//                    }
//
//                    $offset = $position;
//
//                    break;
//
//                case preg_match('/^form-data; name=\"(.*)\"$/', $headers->get('Content-Disposition'), $matches):
//
//                    $delimiter = sprintf('%s--%s', "\r\n", $this->boundary);
//
//                    if (false === $position = strpos($data, $delimiter, $offset)) {
//                        $body = substr($data, $offset);
//                        $parseDone = true;
//                    } else {
//                        $body = substr($data, $offset, $position - $offset);
//                    }
//
//                    $field = new FormField($matches[1]);
//                    $this->emit('data', [$field]);
//                    $field->emit('data', [$body]);
//
//                    $offset = $position;
//
//                    break;
//            }
//        }
//
//        if ($isEnd) {
//            $this->emit('end');
//        }
    }

    /**
     * @param FormField $field
     * @param           $data
     * @param bool      $isEnd
     *
     * @throws \Exception
     */
    private function processFileData(FormField $field, $data, $isEnd = false)
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
            $this->parseData(substr($data, $position), $isEnd);
        }
    }

    private $writable = true;

    public function isWritable()
    {
        return $this->writable;
    }

    public function write($data)
    {
        echo 'ok';
    }

    public function end($data = null)
    {
        echo 'ok';
    }

    public function close()
    {
        $this->writable = false;
    }
}