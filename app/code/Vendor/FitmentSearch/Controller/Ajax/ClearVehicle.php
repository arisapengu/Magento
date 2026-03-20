<?php
declare(strict_types=1);
namespace Vendor\FitmentSearch\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;

class ClearVehicle implements HttpPostActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly CustomerSession $customerSession
    ) {}

    public function execute()
    {
        $this->customerSession->unsFitmentSelectedVehicle();
        return $this->jsonFactory->create()->setData(['success' => true]);
    }
}
