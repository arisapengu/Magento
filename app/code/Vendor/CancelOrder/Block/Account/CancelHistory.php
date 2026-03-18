<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Block\Account;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Vendor\CancelOrder\Model\ResourceModel\Request\CollectionFactory;

class CancelHistory extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly CustomerSession   $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getRequests()
    {
        $customerId = (int) $this->customerSession->getCustomerId();
        return $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'DESC');
    }

    public function getOrderUrl(string $incrementId): string
    {
        return $this->getUrl('sales/order/view', ['order_id' => $incrementId]);
    }

    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'approved' => __('Cancelled'),
            'rejected' => __('Rejected'),
            default    => __('Pending Review'),
        };
    }

    public function getStatusClass(string $status): string
    {
        return match ($status) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            default    => 'pending',
        };
    }
}
