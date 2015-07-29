<?php

namespace React\Http\Handler;

use Evenement\EventEmitter;
use React\Http\Processor\DataProcessorFactory;
use React\Http\Processor\FormField;
use React\Http\Processor\MultipartDataProcessor;
use React\Http\Request;

/**
 * Class RequestHandler
 *
 * @package React\Http\Handler
 */
class RequestHandler extends EventEmitter
{
    /**
     * @var DataProcessorFactory
     */
    protected $processorFactory;

    /**
     * @var MultipartDataProcessor
     */
    protected $processor;

    /**
     * @var bool
     */
    private $closed = false;

    /**
     * Create request handler
     */
    public function __construct()
    {
        $this->processorFactory = new DataProcessorFactory();
    }

    /**
     * @param Request $request
     */
    public function handle(Request $request)
    {
        $this->processor = $this->processorFactory->get($request);

//        $this->processor = new MultipartDataProcessor($request);

        if ($this->processor === null) {
            $this->emit('end');
            return;
        }

        $this->processor->on('data', function (FormField $field) {
            if (false == $this->closed) {
                $this->emit('data', [$field]);
            }
        });

        $this->processor->on('end', function () {
            if (false === $this->closed) {
                $this->closed = true;
                $this->emit('end');
            }
        });

        $request->on('data', function ($data, $end) {
            $this->processor->process($data, $end);
        });

//        $request->pipe($this->processor);
    }
}