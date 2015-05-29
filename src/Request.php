<?php

namespace React\Http;

use Evenement\EventEmitter;
use React\Http\Foundation\HeaderDictionary;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;

/**
 * Class Request
 *
 * @event   data
 * @event   pause
 * @event   resume
 * @event   end
 *
 * @package React\Http
 */
class Request extends EventEmitter implements ReadableStreamInterface
{
    /**
     * @var HeaderDictionary
     */
    public $headers;

    private $readable = true;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $query;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    public $remoteAddress;

    /**
     * @param string $method
     * @param string $path   Default value is "/"
     *                       (http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.2.3)
     * @param array  $query
     * @param string $version
     * @param array  $headers
     */
    public function __construct($method, $path = '/', $query = array(), $version = '1.1', $headers = array())
    {
        $this->setMethod($method);
        $this->path = $path;
        $this->query = $query;
        $this->version = $version;
        $this->headers = new HeaderDictionary($headers);
    }

    /**
     * Method is case-insensitive (http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html#sec5.1.1)
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = strtolower($method);
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->method === strtolower($method);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return bool
     */
    public function expectsContinue()
    {
        return isset($this->headers['Expect']) && '100-continue' === $this->headers['Expect'];
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * Emit pause
     */
    public function pause()
    {
        $this->emit('pause');
    }

    /**
     * Emit resume
     */
    public function resume()
    {
        $this->emit('resume');
    }

    /**
     * Close
     */
    public function close()
    {
        $this->readable = false;
        $this->emit('end');
        $this->removeAllListeners();
    }

    /**
     * @param WritableStreamInterface $dest
     * @param array                   $options
     *
     * @return WritableStreamInterface
     */
    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }
}
