<?php

declare(strict_types=1);

namespace TruAuto\RmaFix\Plugin\Block\Customer\Rma;

use MageOS\RMA\Block\Customer\Rma\ListRma;
use Magento\Framework\Exception\NoSuchEntityException;

class ListRmaPlugin
{
    public function aroundGetOrderIncrementId(ListRma $subject, callable $proceed, int $orderId): string
    {
        try {
            return $proceed($orderId);
        } catch (NoSuchEntityException) {
            return (string)$orderId;
        }
    }
}