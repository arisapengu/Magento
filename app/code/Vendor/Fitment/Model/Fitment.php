<?php

declare(strict_types=1);

namespace Vendor\Fitment\Model;

use Magento\Framework\Model\AbstractModel;
use Vendor\Fitment\Model\ResourceModel\Fitment as FitmentResource;

class Fitment extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'vendor_fitment';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(FitmentResource::class);
    }
}