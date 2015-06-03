<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 29.05.15
 * Time: 18:22
 */

namespace React\Http\Processor;

use Evenement\EventEmitter;
use React\Stream\WritableStreamInterface;

abstract class AbstractProcessor extends EventEmitter implements ProcessorInterface, WritableStreamInterface
{
    abstract public function process($data);
}