<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Controller\Adminhtml\Vehicle;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Vendor\Vehicle\Model\ResourceModel\Vehicle as VehicleResource;

class MakeSuggest extends Action
{
    const ADMIN_RESOURCE = 'Vendor_Vehicle::vehicle_manage';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly VehicleResource $vehicleResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $makes  = $this->vehicleResource->getDistinctMakes();
        $result = $this->jsonFactory->create();
        return $result->setData($makes);
    }
}
