<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 20.05.15
 * Time: 23:40
 */

namespace React\Http\Processor;


use React\Http\Request;

/**
 * Class DataProcessorFactory
 *
 * @package React\Http\Processor
 */
class DataProcessorFactory
{
    /**
     * @param Request $request
     *
     * @return MultipartDataProcessor|UrlencodedDataProcessor
     */
    function get(Request $request)
    {
        $contentType = $request->headers->get('Content-Type', '');

        switch (true) {
            case (strpos($contentType, 'multipart') !== false):
                $processor = new MultipartDataProcessor($request);
                break;

            case ($contentType == 'application/x-www-form-urlencoded'):
                $processor = new UrlencodedDataProcessor($request);
                break;

            default:
                $processor = new UrlencodedDataProcessor($request);
        }

        return $processor;
    }
}