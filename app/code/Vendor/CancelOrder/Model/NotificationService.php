<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    private const XML_ADMIN_EMAIL = 'trans_email/ident_general/email';
    private const XML_ADMIN_NAME  = 'trans_email/ident_general/name';

    public function __construct(
        private readonly TransportBuilder     $transportBuilder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface      $logger
    ) {}

    /**
     * Notify admin: new cancel request received.
     */
    public function notifyAdminNewRequest(
        Order   $order,
        string  $reason,
        bool    $immediate = false,
        ?Request $request  = null
    ): void {
        $adminEmail = $this->scopeConfig->getValue(self::XML_ADMIN_EMAIL, ScopeInterface::SCOPE_STORE);
        $adminName  = $this->scopeConfig->getValue(self::XML_ADMIN_NAME,  ScopeInterface::SCOPE_STORE);

        $this->send(
            'Vendor_CancelOrder::email/admin_notify.html',
            [
                'order'         => $order,
                'order_id'      => $order->getIncrementId(),
                'customer_name' => $order->getCustomerName(),
                'reason'        => $reason,
                'immediate'     => $immediate,
                'request_id'    => $request?->getId() ?? 0,
            ],
            $adminEmail,
            $adminName,
            'Cancel Order Request - #' . $order->getIncrementId()
        );
    }

    /**
     * Notify customer: cancellation approved + refund processed.
     */
    public function notifyCustomerApproved(Order $order, Request $request): void
    {
        $this->send(
            'Vendor_CancelOrder::email/customer_approved.html',
            [
                'order'         => $order,
                'order_id'      => $order->getIncrementId(),
                'customer_name' => $order->getCustomerFirstname(),
                'admin_note'    => $request->getAdminNote() ?: '',
            ],
            $order->getCustomerEmail(),
            $order->getCustomerName(),
            'Your Order #' . $order->getIncrementId() . ' Has Been Cancelled'
        );
    }

    /**
     * Notify customer: cancellation rejected.
     */
    public function notifyCustomerRejected(Order $order, Request $request): void
    {
        $this->send(
            'Vendor_CancelOrder::email/customer_rejected.html',
            [
                'order'         => $order,
                'order_id'      => $order->getIncrementId(),
                'customer_name' => $order->getCustomerFirstname(),
                'admin_note'    => $request->getAdminNote() ?: '',
            ],
            $order->getCustomerEmail(),
            $order->getCustomerName(),
            'Update on Your Cancel Request - Order #' . $order->getIncrementId()
        );
    }

    private function send(
        string $templatePath,
        array  $vars,
        string $toEmail,
        string $toName,
        string $subject
    ): void {
        try {
            $storeId = $this->storeManager->getStore()->getId();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templatePath)
                ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
                ->setTemplateVars(array_merge($vars, ['subject' => $subject]))
                ->setFromByScope('general')
                ->addTo($toEmail, $toName)
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('CancelOrder email error: ' . $e->getMessage());
        }
    }
}
