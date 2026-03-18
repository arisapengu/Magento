<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Model;

use Magento\Framework\Model\AbstractModel;

class Request extends AbstractModel
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected function _construct(): void
    {
        $this->_init(\Vendor\CancelOrder\Model\ResourceModel\Request::class);
    }

    public function getStatusLabel(): string
    {
        return match ($this->getStatus()) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default               => 'Pending',
        };
    }
}
