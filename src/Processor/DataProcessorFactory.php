<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 20.05.15
 * Time: 23:40
 */

namespace React\Http\Processor;


use React\Http\Request;

class DataProcessorFactory
{
    function get(Request $request)
    {
        if (null === $contentType = $request->headers->get('Content-Type')) {
            return null;
        }

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