<?php

namespace Digipeyk\PaymentClient;

class Invoice
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $salt;

    /**
     * @var bool
     */
    public $done;

    /**
     * @var null|bool
     */
    public $verified;

    /**
     * @var null|array
     */
    public $details;

    public function __construct($id, $salt, $done, $verified, $details)
    {
        $this->id = $id;
        $this->salt = $salt;
        $this->done = $done;
        $this->verified = $verified;
        $this->details = $details;
    }
}
