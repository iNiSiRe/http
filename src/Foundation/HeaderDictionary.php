<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 21.05.15
 * Time: 1:43
 */

namespace React\Http\Foundation;

use React\Http\Utils\Dictionary;

/**
 * Class HeaderDictionary
 *
 * HTTP message headers names are case-insensitive
 * (http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2)
 *
 * @package React\Http\Foundation
 */
class HeaderDictionary extends Dictionary
{
    /**
     * @param array $items
     */
    public function __construct($items = [])
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return null
     */
    public function get($key, $default = null)
    {
        return parent::get(mb_strtolower($key), $default);
    }

    /**
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        parent::set(mb_strtolower($key), $value);
    }
}