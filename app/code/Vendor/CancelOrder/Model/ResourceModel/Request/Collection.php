<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Model\ResourceModel\Request;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(
            \Vendor\CancelOrder\Model\Request::class,
            \Vendor\CancelOrder\Model\ResourceModel\Request::class
        );
    }
}
