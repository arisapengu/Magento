<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Controller\Adminhtml\Vehicle;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\Controller\Result\RedirectFactory;
use Vendor\Vehicle\Model\VehicleFactory;

class Edit extends Action
{
    const ADMIN_RESOURCE = 'Vendor_Vehicle::vehicle_manage';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly VehicleFactory $vehicleFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $vehicle = $this->vehicleFactory->create();

        if ($id) {
            $vehicle->load($id);
            if (!$vehicle->getId()) {
                $this->messageManager->addErrorMessage(__('This vehicle no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Vendor_Vehicle::vehicle_manage');
        $resultPage->getConfig()->getTitle()->prepend(
            $vehicle->getId() ? __('Edit Vehicle') : __('New Vehicle')
        );
        return $resultPage;
    }
}
