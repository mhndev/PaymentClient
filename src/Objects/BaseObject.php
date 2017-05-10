<?php

namespace Digipeyk\PaymentClient\Objects;

class BaseObject
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
        $value = is_null($this->data[$name]) ? null : $this->data[$name];
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
}
