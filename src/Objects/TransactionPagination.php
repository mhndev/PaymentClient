<?php

namespace Digipeyk\PaymentClient\Objects;

class TransactionPagination
{
    /**
     * @var Transaction[]
     */
    public $transactions;

    /**
     * @var int
     */
    public $total;

    public function __construct(array $transactions, $total)
    {
        $this->transactions = $transactions;
        $this->total = $total;
    }
}
