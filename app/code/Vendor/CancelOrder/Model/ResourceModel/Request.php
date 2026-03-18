<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Request extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('vendor_cancel_order_request', 'request_id');
    }
}
