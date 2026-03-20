<?php

declare(strict_types=1);

namespace Vendor\Fitment\Model\ResourceModel\Fitment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Vendor\Fitment\Model\Fitment;
use Vendor\Fitment\Model\ResourceModel\Fitment as FitmentResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize collection
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(Fitment::class, FitmentResource::class);
    }
}