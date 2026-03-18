<?php
namespace Vendor\ApiShipping\Plugin\Adminhtml\Shipment;

use Magento\Backend\Block\Widget\Form\Container;

class AddLalamoveButton
{
    /**
     * Add "Send to Lalamove" button on both:
     *  - New shipment page  (Magento\Shipping\Block\Adminhtml\Create)
     *  - Shipment view page (Magento\Shipping\Block\Adminhtml\View)
     *
     * @param Container $subject
     */
    public function beforeSetLayout(Container $subject): void
    {
        $shipment = $subject->getShipment();
        if (!$shipment) {
            return;
        }

        $order          = $shipment->getOrder();
        $shippingMethod = $order ? $order->getShippingMethod() : '';

        if (strpos($shippingMethod, 'lalamove_') !== 0) {
            return;
        }

        // --- Shipment VIEW page: shipment already saved ---
        if ($shipment->getId()) {
            foreach ($shipment->getAllTracks() as $track) {
                if ($track->getCarrierCode() === 'lalamove') {
                    $subject->addButton('lalamove_dispatched', [
                        'label'   => __('Lalamove: Dispatched ✓'),
                        'class'   => 'action-default disabled',
                        'onclick' => '',
                    ]);
                    return;
                }
            }

            $sendUrl = $subject->getUrl(
                'apishipping/lalamove/send',
                ['shipment_id' => $shipment->getId()]
            );
            $confirm = $subject->escapeJs(__('Send this shipment to Lalamove?'));
            $subject->addButton('send_to_lalamove', [
                'label'   => __('Send to Lalamove'),
                'class'   => 'action-default action-secondary',
                'onclick' => "confirmSetLocation('{$confirm}', '{$sendUrl}')",
            ]);
            return;
        }

        // --- New shipment CREATE page: shipment not yet saved ---
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
        $confirm = $subject->escapeJs(__('Create shipment and send to Lalamove?'));
        $subject->addButton('send_to_lalamove', [
            'label'   => __('Send to Lalamove'),
            'class'   => 'action-default action-secondary',
            'onclick' => "confirmSetLocation('{$confirm}', '{$sendUrl}')",
        ]);
    }
}
