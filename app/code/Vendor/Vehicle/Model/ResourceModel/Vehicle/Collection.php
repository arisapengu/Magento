<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Model\ResourceModel\Vehicle;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Vendor\Vehicle\Model\Vehicle;
use Vendor\Vehicle\Model\ResourceModel\Vehicle as VehicleResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'vendor_vehicle_collection';
    protected $_eventObject = 'vehicle_collection';

    protected function _construct(): void
    {
        $this->_init(Vehicle::class, VehicleResource::class);
    }
}
