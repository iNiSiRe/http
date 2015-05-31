<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 28.05.15
 * Time: 15:33
 */

namespace React\Http\Processor;


use Evenement\EventEmitter;
use React\Http\Utils\Dictionary;

class FormField extends EventEmitter
{
    const ORIGINAL_FILENAME = 'original_filename';

    /**
     * @var string
     */
    private $name;

    public $attributes;

    private $file = false;

    /**
     * @param string $name
     */
    public function __construct($name = '')
    {
        $this->name = $name;
        $this->attributes = new Dictionary();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return boolean
     */
    public function isFile()
    {
        return $this->file;
    }

    /**
     * @param boolean $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }
}