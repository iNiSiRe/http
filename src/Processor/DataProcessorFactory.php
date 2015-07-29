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
                return new MultipartDataProcessor($request);

            case ($contentType == 'application/x-www-form-urlencoded'):
                return new UrlencodedDataProcessor($request);
        }

        return null;
    }
}