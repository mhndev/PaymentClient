<?php

namespace Digipeyk\PaymentClient\Objects;

/**
 * @property int $id
 * @property string $salt
 * @property string $redirect_url
 * @property string|null $description
 * @property GatewayTransaction|null $transaction
 * @property int $wallet_id
 * @property int $transaction_id
 * @property string $created_at
 * @property string|null $done_at
 * @property string|null $verified_at
 */
class Invoice extends BaseObject
{
    protected $relations = [
        'transaction' => GatewayTransaction::class,
    ];
}
