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

    private $requestString = '';

    public function __construct(Request $request)
    {
        $this->events = new EventEmitter();
        $this->boundary = $this->parseBoundary($request->headers->get('Content-Type'));
        $this->buffer = '';

        $this->events->on('data', [$this, 'handleParsedData']);
        $this->events->on('end', [$this, 'handleProcessed']);
    }

    public function handleProcessed()
    {
        $processor = new UrlencodedDataProcessor();

        $processor->on('data', function (FormField $field) {
            $this->emit('data', [$field]);
        });

        $processor->on('end', function () {
           $this->emit('end');
        });

        $processor->process($this->requestString);
    }

    public function handleParsedData(FormField $field)
    {
        if ($field->isFile()) {
            $this->handleMultipart($field);
        } else {
            $this->handlePlain($field);
        }
    }

    public function handlePlain(FormField $field)
    {
        $buffer = '';

        $field->on('data', function ($data) use (&$buffer) {
            $buffer .= $data;
        });

        $field->on('end', function ($data) use (&$buffer, $field) {
            $this->requestString = empty($this->requestString)
                ? $field->getName() . '=' . urlencode($buffer . $data)
                : $this->requestString . '&' . $field->getName() . '=' . urlencode($buffer . $data);
            $buffer = '';
        });
    }

    public function handleMultipart(FormField $field)
    {
        $this->emit('data', [$field]);
    }

    public function process($data)
    {
        $this->parseData($data);
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
                $this->events->emit('data', [$field]);
                $state = self::STATE_FILE_DATA;
                break;

            case preg_match('/^form-data; name=\"(.*)\"$/', $headers->get('Content-Disposition'), $matches):
                $field = new FormField($matches[1]);
                $this->field = $field;
                $this->events->emit('data', [$field]);
                $state = self::STATE_FILE_DATA;
                break;

            default:
                throw new \Exception("Bad Content-Disposition header with content '{$headers->get('Content-Disposition')}'");
        }

        return $state;
    }

    protected function isPartialEnd($data, $flag)
    {
        $length = strlen($data);
        $char = $data[$length - 1];

        $offset = 0;
        while (false !== $position = strpos($flag, $char, $offset)) {
            $offset = $position + 1;

            if ($position === 0) {
                return true;
            }

            $part = substr($flag, 0, $position) . $char;
            $position = strrpos($data, $part);
            if ($position === false) {
                continue;
            }

            if ($position + strlen($part) == $length) {
                return true;
            }
        }

        return false;
    }

    /**
     * Base data processor
     *
     * @param      $data
     *
     * @throws \Exception
     */
    protected function parseData($data)
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
                    $flag = "\r\n\r\n";

                    if ($this->buffer) {
                        $data = $this->buffer . $data;
                        $this->buffer = '';
                    }

                    $position = strpos($data, $flag, $offset);
                    if ($position === false) {
//                        throw new \Exception('Bad multipart request');
                        $this->buffer .= substr($data, $offset);
                        $parseDone = true;
                        break;
                    }
                    $raw = substr($data, $offset, $position - $offset);
                    $headers = $this->parseHeaders($raw);
                    $offset = $position + strlen($flag);
                    $this->state = $this->getStateByHeaders($headers);
                    break;

                case self::STATE_FILE_DATA:

                    if ($this->field === null) {
                        throw new \Exception('Field not created');
                    }

                    if ($this->buffer) {
                        $data = $this->buffer . $data;
                        $this->buffer = '';
                    }

                    $endFlag = "\r\n--{$this->boundary}";
                    $position = strpos($data, $endFlag, $offset);
                    if ($position === false && $this->isPartialEnd($data, $endFlag)) {
                        $this->buffer = substr($data, $offset);
                        $parseDone = true;
                    } elseif ($position === false) {
                        $this->field->emit('data', [substr($data, $offset)]);
                        $parseDone = true;
                    } else {
                        $this->field->emit('end', [substr($data, $offset, $position - $offset)]);
                        $offset = $position + strlen($endFlag);
                        $this->state = self::STATE_BLOCK_END;
                    }
                    break;
                
                case self::STATE_BLOCK_END:
                    if ($this->validate($data, '--', $offset)) {
                        $this->events->emit('end');
                        $parseDone = true;
                    } elseif ($this->validate($data, "\r\n", $offset)) {
                        $this->state = self::STATE_HEADER_BEGIN;
                    }
                    break;
            }
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