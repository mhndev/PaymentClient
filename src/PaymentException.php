<?php

namespace Digipeyk\PaymentClient;

use Exception;

class PaymentException extends Exception
{
    /**
     * @var array
     */
    public $errors;

    public function __construct($message = "", $code = 0, $errors = [], Exception $previous = null)
    {
        $this->errors = $errors;

        if ($this->errors) {
            $message .= ': '. json_encode($this->errors, JSON_PRETTY_PRINT);
        }

        parent::__construct($message, $code, $previous);
    }
}
