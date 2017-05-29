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

    /**
     * @var int
     */
    public $perPage;

    /**
     * @var int
     */
    public $currentPage;

    /**
     * @var int
     */
    public $lastPage;

    public function __construct(array $transactions, $total, $perPage, $currentPage, $lastPage)
    {
        $this->transactions = $transactions;
        $this->total = $total;
    }
}
