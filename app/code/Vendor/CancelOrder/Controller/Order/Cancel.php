<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Controller\Order;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Vendor\CancelOrder\Model\CancelService;

class Cancel implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface        $request,
        private readonly JsonFactory             $jsonFactory,
        private readonly FormKeyValidator        $formKeyValidator,
        private readonly CustomerSession         $customerSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CancelService           $cancelService
    ) {}

    public function execute()
    {
        $result = $this->jsonFactory->create();

        // Validate form key (CSRF)
        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData(['success' => false, 'message' => __('Invalid request.')]);
        }

        // Must be logged in
        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['success' => false, 'message' => __('Please sign in to cancel your order.')]);
        }

        $orderId = (int) $this->request->getParam('order_id');
        $reason  = trim((string) $this->request->getParam('reason', ''));

        if (!$orderId) {
            return $result->setData(['success' => false, 'message' => __('Invalid order.')]);
        }

        try {
            $order      = $this->orderRepository->get($orderId);
            $customerId = (int) $this->customerSession->getCustomerId();

            $cancelResult = $this->cancelService->requestCancel($order, $customerId, $reason);

            $message = $cancelResult['immediate']
                ? __('Your order #%1 has been successfully cancelled.', $order->getIncrementId())
                : __('Your cancellation request for order #%1 has been submitted. Our team will review it shortly.', $order->getIncrementId());

            return $result->setData([
                'success'   => true,
                'immediate' => $cancelResult['immediate'],
                'message'   => $message,
            ]);

        } catch (LocalizedException $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => __('An error occurred. Please try again.')]);
        }
    }
}
