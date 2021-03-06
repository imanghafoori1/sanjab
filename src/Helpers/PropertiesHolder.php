<?php

namespace Sanjab\Helpers;

use Closure;
use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable;
use Opis\Closure\SerializableClosure;

class PropertiesHolder implements Arrayable, JsonSerializable
{
    /**
     * List of properties.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * List of getter functions that should be present in json response.
     *
     * @var array
     */
    protected $getters = [];

    /**
     * List of properties that should be hidden from json response.
     *
     * @var array
     */
    protected $hidden = [];

    public function __construct(array $properties = [])
    {
        $this->properties = array_merge($this->properties, $properties);
    }

    public function setIt($prop, $value)
    {
        if ($value) {
            $this->$prop($value);
        }

        return $this;
    }

    public function __call($method, $arguments)
    {
        if (count($arguments) == 1) {
            $this->setProperty($method, array_first($arguments));
        }

        return $this;
    }

    public function __get($name)
    {
        $method = 'get'.str_replace(['-', '_'], '', title_case($name));
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], []);
        }

        return $this->properties[$name] ?? null;
    }

    /**
     * Getters.
     *
     * @return array
     */
    public function getGetters()
    {
        return $this->getters;
    }

    public function toArray()
    {
        $out = $this->properties;
        foreach ($this->getGetters() as $getter) {
            $out[$getter] = $this->__get($getter);
        }

        return array_filter($out, function ($property, $key) {
            return ! in_array($key, $this->hidden);
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Get property.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function property(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->properties;
        }

        return array_get($this->properties, $key, $default);
    }

    /**
     * Set property.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setProperty($key, $value)
    {
        if ($value instanceof Closure) {
            $value = new SerializableClosure($value);
        }
        $this->properties[$key] = $value;

        return $this;
    }

    /**
     * create new Properties Holder.
     *
     * @return static
     */
    public static function create()
    {
        return new static;
    }
}
