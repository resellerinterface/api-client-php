<?php

namespace ResellerInterface\Api\Response;

use ArrayAccess;
use ResellerInterface\Api\Exception\InvalidResponseException;

class Response implements ArrayAccess
{
    protected $response;

    /**
     * Response constructor.
     *
     * @param $rawData
     *
     * @throws InvalidResponseException
     */
    public function __construct($rawData)
    {
        $result = json_decode($rawData, true);
        $result = (json_last_error() === JSON_ERROR_NONE ? $result : false);

        if ($result === false) {
            throw new InvalidResponseException("coreAPI response contains invalid JSON", 0, null, $rawData);
        }

        $this->response = $result;
    }

    /**
     * @return integer|null
     */
    public function getState()
    {
        return isset($this->response['state']) ? $this->response['state'] : null;
    }

    /**
     * @return string|null
     */
    public function getStateName()
    {
        return isset($this->response['stateName']) ? $this->response['stateName'] : null;
    }

    /**
     * @return string|null
     */
    public function getStateParam()
    {
        return isset($this->response['stateParam']) ? $this->response['stateParam'] : null;
    }

    /**
     * @return array|null
     */
    public function getData()
    {
        return isset($this->response) ? $this->response : null;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return !$this->getState() || $this->getState() >= 2000;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        if (isset($this->response['errors'])) {
            return $this->response['errors'];
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->response[$offset]);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->response[$offset];
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->response[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->response[$offset]);
    }
}
