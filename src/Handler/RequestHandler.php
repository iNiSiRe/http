<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 20.05.15
 * Time: 17:44
 */

namespace React\Http\Handler;


use Evenement\EventEmitter;
use React\Http\Processor\DataProcessorFactory;
use React\Http\Processor\MultipartDataProcessor;
use React\Http\Request;

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

    public function __construct()
    {
        $this->processorFactory = new DataProcessorFactory();
    }

    public function handle(Request $request)
    {
        $this->emit('request', array($request));

        $this->processor = $this->processorFactory->get($request);

        if ($this->processor === null) {
            $request->emit('end');
            return;
        }

        $this->processor->on('end', function () use ($request) {
            $request->emit('end');
        });

        $this->processData($request->getBody());

        $request->on('data', function ($data) use ($request) {
            $this->processData($data);
        });
    }

    public function processData($data)
    {
        $this->processor->process( $data);
    }
}