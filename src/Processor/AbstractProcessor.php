<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 29.05.15
 * Time: 18:22
 */

namespace React\Http\Processor;

use Evenement\EventEmitter;

abstract class AbstractProcessor extends EventEmitter implements ProcessorInterface
{
    abstract public function process($data);
}