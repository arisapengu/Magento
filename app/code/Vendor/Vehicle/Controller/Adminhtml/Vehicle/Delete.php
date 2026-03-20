<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Controller\Adminhtml\Vehicle;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Vendor\Vehicle\Model\VehicleFactory;
use Vendor\Vehicle\Model\ResourceModel\Vehicle as VehicleResource;

class Delete extends Action
{
    const ADMIN_RESOURCE = 'Vendor_Vehicle::vehicle_manage';

    public function __construct(
        Context $context,
        private readonly VehicleFactory $vehicleFactory,
        private readonly VehicleResource $vehicleResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('Invalid vehicle ID.'));
            return $redirect->setPath('*/*/index');
        }

        $vehicle = $this->vehicleFactory->create();
        $this->vehicleResource->load($vehicle, $id);

        if (!$vehicle->getId()) {
            $this->messageManager->addErrorMessage(__('Vehicle not found.'));
            return $redirect->setPath('*/*/index');
        }

        try {
            $this->vehicleResource->delete($vehicle);
            $this->messageManager->addSuccessMessage(__('Vehicle deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $redirect->setPath('*/*/index');
    }
}
