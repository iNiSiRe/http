<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 21.05.15
 * Time: 19:05
 */

namespace React\Http\Foundation;


use Evenement\EventEmitter;

class File extends EventEmitter
{
    private $name;
    private $path;
    private $type;

    public function __construct($name = null, $type = null, $path = null)
    {
        $this->name = $name;
        $this->path = $path;
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }
}