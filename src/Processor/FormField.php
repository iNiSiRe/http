<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 28.05.15
 * Time: 15:33
 */

namespace React\Http\Processor;


use Evenement\EventEmitter;

class FormField extends EventEmitter
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = '')
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}