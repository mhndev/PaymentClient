<?php

namespace Digipeyk\PaymentClient\Objects;


class TransferAndPayDescriptions
{
    public $srcDischarge;
    public $targetCharge;
    public $targetDischarge;

    public function __construct($srcDischarge, $targetCharge, $targetDischarge)
    {
        $this->srcDischarge = $srcDischarge;
        $this->targetCharge = $targetCharge;
        $this->targetDischarge = $targetDischarge;
    }

    public function toArray()
    {
        return [
            'from' => $this->srcDischarge,
            'to' => [
                'charge' => $this->targetCharge,
                'discharge' => $this->targetDischarge,
            ]
        ];
    }
}
