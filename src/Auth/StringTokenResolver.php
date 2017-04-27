<?php

namespace Digipeyk\PaymentClient\Auth;


use Exception;

class StringTokenResolver implements TokenResolverInterface
{
    /**
     * @var string
     */
    private $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function refreshToken()
    {
        throw new Exception('not implemented');
    }

    public function getToken()
    {
        return $this->token;
    }
}
