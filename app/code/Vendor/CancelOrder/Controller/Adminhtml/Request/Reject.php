<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Controller\Adminhtml\Request;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Vendor\CancelOrder\Model\CancelService;
use Vendor\CancelOrder\Model\RequestFactory;
use Vendor\CancelOrder\Model\ResourceModel\Request as RequestResource;

class Reject extends Action
{
    public const ADMIN_RESOURCE = 'Vendor_CancelOrder::cancel_requests';

    public function __construct(
        Context $context,
        private readonly JsonFactory     $jsonFactory,
        private readonly CancelService   $cancelService,
        private readonly RequestFactory  $requestFactory,
        private readonly RequestResource $requestResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result    = $this->jsonFactory->create();
        $requestId = (int) $this->getRequest()->getParam('request_id');
        $adminNote = trim((string) $this->getRequest()->getParam('admin_note', ''));

        try {
            $request = $this->requestFactory->create();
            $this->requestResource->load($request, $requestId);

            if (!$request->getId()) {
                throw new LocalizedException(__('Request not found.'));
            }

            $this->cancelService->reject($request, $adminNote);

            return $result->setData(['success' => true, 'message' => __('Request rejected.')]);
        } catch (LocalizedException $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => __('An error occurred.')]);
        }
    }
}
