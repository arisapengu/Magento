<?php
namespace Vendor\ApiShipping\Plugin\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View;

class AddLalamoveButton
{
    /**
     * Add "Send to Lalamove" button on the admin order view page.
     * Only shows when the order's shipping method is Lalamove.
     */
    public function beforeSetLayout(View $subject): void
    {
        $order          = $subject->getOrder();
        $shippingMethod = $order->getShippingMethod();

        // Only show button for Lalamove orders
        if (strpos($shippingMethod, 'lalamove_') !== 0) {
            return;
        }

        // Don't show if already dispatched (tracking number saved)
        if ($order->getData('lalamove_order_id')) {
            $subject->addButton('lalamove_dispatched', [
                'label'   => __('Lalamove: Dispatched ✓'),
                'class'   => 'action-default disabled',
                'onclick' => '',
            ]);
            return;
        }

        $sendUrl = $subject->getUrl(
            'apishipping/lalamove/send',
            ['order_id' => $order->getId()]
        );

        $confirm = __('Confirm sending this order to Lalamove?');
        $subject->addButton('send_to_lalamove', [
            'label'   => __('Send to Lalamove'),
            'class'   => 'action-default action-secondary',
            'onclick' => "confirmSetLocation('{$confirm}', '{$sendUrl}')",
        ]);
    }
}
