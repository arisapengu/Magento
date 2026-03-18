<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class CancelService
{
    private const XML_ENABLED        = 'cancel_order/general/enabled';
    private const XML_ALLOWED_STATES = 'cancel_order/general/allowed_states';
    private const XML_REASONS        = 'cancel_order/general/cancel_reasons';

    public function __construct(
        private readonly ScopeConfigInterface      $scopeConfig,
        private readonly OrderRepositoryInterface  $orderRepository,
        private readonly OrderManagementInterface  $orderManagement,
        private readonly RequestFactory            $requestFactory,
        private readonly ResourceModel\Request     $requestResource,
        private readonly NotificationService       $notificationService,
        private readonly CreditmemoFactory         $creditmemoFactory,
        private readonly CreditmemoService         $creditmemoService,
        private readonly LoggerInterface           $logger
    ) {}

    /**
     * Customer requests cancellation.
     * - Not shipped: cancel immediately
     * - Already shipped: create pending request, notify admin
     *
     * @throws LocalizedException
     */
    public function requestCancel(Order $order, int $customerId, string $reason): array
    {
        if (!$this->canRequestCancel($order, $customerId)) {
            throw new LocalizedException(__('This order cannot be cancelled.'));
        }

        if (!$order->hasShipments()) {
            // ยังไม่ ship — สร้าง credit memo ก่อน cancel (ต้องการ order state active)
            $creditmemoId = $this->createCreditMemo($order);
            $this->doCancelOrder($order, $reason);
            $this->logger->debug('CancelOrder immediate: creditmemoId=' . ($creditmemoId ?? 'null'));

            $request = $this->createRequest($order, $customerId, $reason, Request::STATUS_APPROVED);
            if ($creditmemoId) {
                $request->setCreditmemoId($creditmemoId);
                $this->requestResource->save($request);
            }
            $this->notificationService->notifyAdminNewRequest($order, $reason, true, $request);
            return ['immediate' => true, 'request_id' => $request->getId()];
        }

        // Order shipped — สร้าง pending request รอ admin
        $request = $this->createRequest($order, $customerId, $reason, Request::STATUS_PENDING);
        $this->notificationService->notifyAdminNewRequest($order, $reason, false, $request);

        return ['immediate' => false, 'request_id' => $request->getId()];
    }

    /**
     * Admin approves the cancel request.
     */
    public function approve(Request $request, string $adminNote = ''): void
    {
        $order = $this->orderRepository->get($request->getOrderId());

        try {
            $this->doCancelOrder($order, $request->getReason());
            $creditmemoId = $this->createCreditMemo($order);

            $request->setStatus(Request::STATUS_APPROVED);
            $request->setAdminNote($adminNote);
            if ($creditmemoId) {
                $request->setCreditmemoId($creditmemoId);
            }
            $this->requestResource->save($request);

            $this->notificationService->notifyCustomerApproved($order, $request);
        } catch (\Exception $e) {
            $this->logger->error('CancelOrder approve: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to approve cancellation: %1', $e->getMessage()));
        }
    }

    /**
     * Admin rejects the cancel request.
     */
    public function reject(Request $request, string $adminNote = ''): void
    {
        $request->setStatus(Request::STATUS_REJECTED);
        $request->setAdminNote($adminNote);
        $this->requestResource->save($request);

        $order = $this->orderRepository->get($request->getOrderId());
        $this->notificationService->notifyCustomerRejected($order, $request);
    }

    public function canRequestCancel(Order $order, int $customerId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }
        if ((int) $order->getCustomerId() !== $customerId) {
            return false;
        }
        // Already has a pending request
        if ($this->hasPendingRequest((int) $order->getEntityId())) {
            return false;
        }
        if (!in_array($order->getState(), $this->getAllowedStates(), true)) {
            return false;
        }
        return $order->canCancel() || $order->hasShipments();
    }

    public function getCancelReasons(): array
    {
        $raw = $this->scopeConfig->getValue(self::XML_REASONS, ScopeInterface::SCOPE_STORE) ?? '';
        return array_filter(array_map('trim', explode("\n", $raw)));
    }

    public function hasPendingRequest(int $orderId): bool
    {
        $request = $this->requestFactory->create();
        $this->requestResource->load($request, $orderId, 'order_id');
        return $request->getId() && $request->getStatus() === Request::STATUS_PENDING;
    }

    private function doCancelOrder(Order $order, string $reason): void
    {
        $comment = $reason
            ? __('Order cancelled. Reason: %1', $reason)
            : __('Order cancelled by customer.');

        $order->addCommentToStatusHistory((string) $comment, Order::STATE_CANCELED, false);
        $this->orderManagement->cancel($order->getEntityId());
    }

    private function createRequest(Order $order, int $customerId, string $reason, string $status = Request::STATUS_PENDING): Request
    {
        $request = $this->requestFactory->create();
        $request->setData([
            'order_id'           => $order->getEntityId(),
            'order_increment_id' => $order->getIncrementId(),
            'customer_id'        => $customerId,
            'customer_name'      => $order->getCustomerName(),
            'customer_email'     => $order->getCustomerEmail(),
            'reason'             => $reason,
            'status'             => $status,
        ]);
        $this->requestResource->save($request);
        return $request;
    }

    private function createCreditMemo(Order $order): ?int
    {
        try {
            $invoices = $order->getInvoiceCollection();

            if (!$invoices->count()) {
                // ไม่มี invoice — สร้าง credit memo จาก order โดยตรง
                $creditmemo = $this->creditmemoFactory->createByOrder($order);
                $creditmemo->addComment(
                    __('Refund issued due to customer cancellation request.'),
                    false,
                    true
                );
                $this->creditmemoService->refund($creditmemo, true);
                return (int) $creditmemo->getEntityId();
            }

            foreach ($invoices as $invoice) {
                $creditmemo = $this->creditmemoFactory->createByOrder($order);
                $creditmemo->setInvoice($invoice);
                $creditmemo->addComment(
                    __('Refund issued due to customer cancellation request.'),
                    false,
                    true
                );
                $this->creditmemoService->refund($creditmemo, true);
                return (int) $creditmemo->getEntityId();
            }
        } catch (\Exception $e) {
            $this->logger->warning('CancelOrder credit memo: ' . $e->getMessage() . ' | Order: ' . $order->getIncrementId());
        }
        return null;
    }

    private function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    private function getAllowedStates(): array
    {
        $raw = $this->scopeConfig->getValue(self::XML_ALLOWED_STATES, ScopeInterface::SCOPE_STORE) ?? '';
        return array_filter(explode(',', $raw));
    }
}
