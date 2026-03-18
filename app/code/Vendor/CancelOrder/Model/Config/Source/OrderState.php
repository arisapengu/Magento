<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\Order;

class OrderState implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => Order::STATE_NEW,             'label' => __('New')],
            ['value' => Order::STATE_PENDING_PAYMENT,  'label' => __('Pending Payment')],
            ['value' => Order::STATE_PROCESSING,       'label' => __('Processing')],
            ['value' => Order::STATE_HOLDED,           'label' => __('On Hold')],
        ];
    }
}
