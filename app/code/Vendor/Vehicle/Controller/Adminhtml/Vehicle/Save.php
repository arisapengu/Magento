<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Controller\Adminhtml\Vehicle;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Vendor\Vehicle\Model\VehicleFactory;
use Vendor\Vehicle\Model\ResourceModel\Vehicle as VehicleResource;

class Save extends Action
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
        $post = $this->getRequest()->getPostValue();
        $redirect = $this->resultRedirectFactory->create();

        if (!$post) {
            return $redirect->setPath('*/*/index');
        }

        // UI component forms wrap fields under 'data' scope
        $data = $post['data'] ?? $post;
        $id = isset($data['id']) ? (int)$data['id'] : null;
        $vehicle = $this->vehicleFactory->create();

        if ($id) {
            $this->vehicleResource->load($vehicle, $id);
            if (!$vehicle->getId()) {
                $this->messageManager->addErrorMessage(__('This vehicle no longer exists.'));
                return $redirect->setPath('*/*/index');
            }
        }

        // Remove empty id so new records get auto-increment
        if (!$id) {
            unset($data['id']);
        }

        // Sanitize year_end: empty string → null
        if (isset($data['year_end']) && $data['year_end'] === '') {
            $data['year_end'] = null;
        }

        $vehicle->setData($data);

        try {
            $this->vehicleResource->save($vehicle);
            $this->messageManager->addSuccessMessage(__('Vehicle saved successfully.'));

            if ($this->getRequest()->getParam('back') === 'edit') {
                return $redirect->setPath('*/*/edit', ['id' => $vehicle->getId()]);
            }
            return $redirect->setPath('*/*/index');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect->setPath('*/*/edit', ['id' => $id]);
        }
    }
}
