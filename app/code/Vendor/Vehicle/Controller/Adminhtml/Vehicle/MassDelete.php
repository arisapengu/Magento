<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Controller\Adminhtml\Vehicle;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Vendor\Vehicle\Model\ResourceModel\Vehicle\CollectionFactory;
use Vendor\Vehicle\Model\ResourceModel\Vehicle as VehicleResource;

class MassDelete extends Action
{
    const ADMIN_RESOURCE = 'Vendor_Vehicle::vehicle_manage';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly VehicleResource $vehicleResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = 0;

        foreach ($collection->getItems() as $vehicle) {
            $this->vehicleResource->delete($vehicle);
            $count++;
        }

        $this->messageManager->addSuccessMessage(__('Deleted %1 vehicle(s).', $count));
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
