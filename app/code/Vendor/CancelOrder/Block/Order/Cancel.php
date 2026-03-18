<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Vendor\CancelOrder\Model\CancelService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Registry;

class Cancel extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly CancelService   $cancelService,
        private readonly CustomerSession $customerSession,
        private readonly Registry        $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getOrder(): ?Order
    {
        return $this->registry->registry('current_order');
    }

    public function canCancel(): bool
    {
        $order      = $this->getOrder();
        $customerId = (int) $this->customerSession->getCustomerId();

        if (!$order || !$customerId) {
            return false;
        }

        return $this->cancelService->canRequestCancel($order, $customerId);
    }

    public function getCancelReasons(): array
    {
        return $this->cancelService->getCancelReasons();
    }

    public function getCancelUrl(): string
    {
        return $this->getUrl('cancelorder/order/cancel');
    }

    public function getOrderId(): int
    {
        return (int) ($this->getOrder()?->getEntityId() ?? 0);
    }

    public function getOrderNumber(): string
    {
        return (string) ($this->getOrder()?->getIncrementId() ?? '');
    }
}
