<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Block\Adminhtml\Request;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Vendor\CancelOrder\Model\Request;
use Vendor\CancelOrder\Model\ResourceModel\Request\CollectionFactory;

class Grid extends Template
{
    protected $_template = 'Vendor_CancelOrder::request/grid.phtml';

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCollection()
    {
        return $this->collectionFactory->create()
            ->setOrder('created_at', 'DESC');
    }

    public function getApproveUrl(int $requestId): string
    {
        return $this->getUrl('vendor_cancelorder/request/approve', ['request_id' => $requestId]);
    }

    public function getRejectUrl(int $requestId): string
    {
        return $this->getUrl('vendor_cancelorder/request/reject', ['request_id' => $requestId]);
    }

    public function getOrderUrl(string $incrementId): string
    {
        return $this->getUrl('sales/order/index', ['increment_id' => $incrementId]);
    }

    public function getCreditmemoUrl(int $creditmemoId): string
    {
        return $this->getUrl('sales/order_creditmemo/view', ['creditmemo_id' => $creditmemoId]);
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    public function getStatusBadge(string $status): string
    {
        return match ($status) {
            Request::STATUS_APPROVED => '<span class="grid-severity-notice"><span>Approved</span></span>',
            Request::STATUS_REJECTED => '<span class="grid-severity-critical"><span>Rejected</span></span>',
            default                  => '<span class="grid-severity-major"><span>Pending</span></span>',
        };
    }
}
