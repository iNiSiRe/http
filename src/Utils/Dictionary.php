<?php
/**
 * Created by PhpStorm.
 * User: inisire
 * Date: 21.05.15
 * Time: 1:18
 */

namespace React\Http\Utils;


class Dictionary
{
    /**
     * @var array
     */
    private $items;

    public function __construct($items = [])
    {
        $this->items = $items;
    }

    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->items)
            ? $this->items[$key]
            : $default;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->items);
    }

    public function set($key, $value)
    {
        $this->items[$key] = $value;
    }
}