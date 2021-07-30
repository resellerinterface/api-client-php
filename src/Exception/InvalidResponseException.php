<?php


namespace ResellerInterface\Api\Exception;

use Exception;

class InvalidResponseException extends Exception
{
    private $response;

    /**
     * InvalidResponseException constructor.
     *
     * @param string $message
     * @param int $code
     * @param null $previous
     * @param null $response
     */
    public function __construct($message = "", $code = 0, $previous = null, $response = null)
    {
        if ($response) {
            $this->response = $response;
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed|null
     */
    public function getResponse()
    {
        if (isset($this->response)) {
            return $this->response;
        }

        return null;
    }

}