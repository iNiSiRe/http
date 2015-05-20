<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 21.05.15
 * Time: 1:43
 */

namespace React\Http\Foundation;

use React\Http\Utils\Dictionary;

class HeaderDictionary extends Dictionary
{
    public function __construct($items = [])
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function get($key, $default = null)
    {
        return parent::get(mb_strtolower($key), $default);
    }

    public function set($key, $value)
    {
        parent::set(mb_strtolower($key), $value);
    }
}