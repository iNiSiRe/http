<?php

namespace React\Http\Handler;

use Evenement\EventEmitter;
use React\Http\Processor\DataProcessorFactory;
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

        if ($this->processor === null) {
            $request->emit('end');
            return;
        }

        $request->on('data', [$this, 'processData']);
    }

    /**
     * @param $data
     */
    public function processData($data)
    {
        $this->processor->process($data);
    }
}