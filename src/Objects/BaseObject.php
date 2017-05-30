<?php

namespace Digipeyk\PaymentClient\Objects;

use JsonSerializable;

class BaseObject implements JsonSerializable
{
    /**
     * @var array
     */
    protected $data;

    protected $relations = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __get($name)
    {
        $value = isset($this->data[$name]) ? $this->data[$name] : null;
        if (is_array($value) && array_key_exists($name, $this->relations)) {
            $class = $this->relations[$name];
            $value = new $class($value);
        }
        return $value;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    function jsonSerialize()
    {
        return $this->data;
    }
}
