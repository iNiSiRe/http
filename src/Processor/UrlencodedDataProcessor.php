<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 20.05.15
 * Time: 23:41
 */

namespace React\Http\Processor;

use Evenement\EventEmitter;
use React\Http\Foundation\File;
use React\Http\Foundation\HeaderDictionary;
use React\Http\Request;

/**
 * Class MultipartDataProcessor
 *
 * @event   data
 *
 * @package React\Http\Processor
 */
class UrlencodedDataProcessor extends AbstractProcessor
{
    public function process($data)
    {
        parse_str($data, $data);
        foreach ($data as $key => $value) {
            $field = new FormField($key);

            if (is_array($value)) {
                array_walk_recursive($value, function (&$value) {
                    $value = urldecode($value);
                });
            } else {
                $value = urldecode($value);
            }

            $this->emit('data', [$field]);
            $field->emit('end', [$value]);
        }
        $this->emit('end');
    }

    public function isWritable()
    {
        // TODO: Implement isWritable() method.
    }

    public function write($data)
    {
        // TODO: Implement write() method.
    }

    public function end($data = null)
    {
        // TODO: Implement end() method.
    }

    public function close()
    {
        // TODO: Implement close() method.
    }
}