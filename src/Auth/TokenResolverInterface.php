<?php

namespace Digipeyk\PaymentClient\Auth;

use Exception;

interface TokenResolverInterface
{
    /**
     * Refresh the token.
     *
     * @return void
     *
     * @throws Exception if not implementable or server reject the request.
     */
    public function refreshToken();

    /**
     * Get the token.
     *
     * @return string
     */
    public function getToken();
}
