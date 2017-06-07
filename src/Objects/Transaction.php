<?php

namespace Digipeyk\PaymentClient\Objects;

/**
 * @property int $id
 * @property int $amount
 * @property int $virtual_amount
 * @property string $unique_key
 * @property int $shop_id
 * @property string $tag
 * @property int $wallet_id
 * @property int|null $discount_id
 * @property int $credit_before
 * @property int $virtual_credit_before
 * @property string|null $description
 * @property int|null $reverse_transaction_id
 * @property int|null $pair_id
 * @property string $created_at
 * @property string $updated_at
 * @property Transaction|null pairedTransaction
 */
class Transaction extends BaseObject
{
    protected $relations = [
        'pairedTransaction' => Transaction::class,
    ];
}
