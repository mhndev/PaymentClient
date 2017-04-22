<?php

namespace Digipeyk\PaymentClient\Exceptions;

use Exception;
use GuzzleHttp\Exception\RequestException;

class PaymentException extends Exception
{
    public $body;

    public function __construct(RequestException $previous = null)
    {
        $this->body = $previous->getResponse() ? (string) $previous->getResponse()->getBody() : null;
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
    }

    /**
     * @return array
     */
    public function getError()
    {
        $decoded = json_decode($this->body, true);
        if (is_array($decoded) && array_key_exists('error', $decoded) && is_array($decoded['error'])) {
            return $decoded['error'];
        }
        return [];
    }

    /**
     * @return string|null
     */
    public function getErrorCode()
    {
        $error = $this->getError();
        if (array_key_exists('code', $error)) {
            return $error['code'];
        }
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        $error = $this->getError();
        if (array_key_exists('message', $error)) {
            return $error['message'];
        }
    }

    /**
     * @return mixed
     */
    public function getErrorInfo()
    {
        $error = $this->getError();
        if (array_key_exists('info', $error)) {
            return $error['info'];
        }
    }
}
