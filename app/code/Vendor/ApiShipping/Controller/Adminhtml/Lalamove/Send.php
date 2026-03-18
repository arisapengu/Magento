<?php
namespace Vendor\ApiShipping\Controller\Adminhtml\Lalamove;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Psr\Log\LoggerInterface;
use Vendor\ApiShipping\Helper\Api\LalamoveOrderApi;

class Send extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Sales::ship';

    private OrderRepositoryInterface $orderRepository;
    private ShipmentFactory $shipmentFactory;
    private TrackFactory $trackFactory;
    private ShipmentRepositoryInterface $shipmentRepository;
    private LalamoveOrderApi $lalamoveOrderApi;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        ShipmentFactory $shipmentFactory,
        TrackFactory $trackFactory,
        ShipmentRepositoryInterface $shipmentRepository,
        LalamoveOrderApi $lalamoveOrderApi,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->orderRepository    = $orderRepository;
        $this->shipmentFactory    = $shipmentFactory;
        $this->trackFactory       = $trackFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->lalamoveOrderApi   = $lalamoveOrderApi;
        $this->logger             = $logger;
    }

    public function execute()
    {
        $shipmentId = (int)$this->getRequest()->getParam('shipment_id');
        $orderId    = (int)$this->getRequest()->getParam('order_id');
        $redirect   = $this->resultRedirectFactory->create();

        try {
            if ($shipmentId) {
                // --- Called from shipment VIEW page: add track to existing shipment ---
                $shipment = $this->shipmentRepository->get($shipmentId);
                $order    = $shipment->getOrder();

                $result     = $this->lalamoveOrderApi->dispatch($order);
                $lalamoveId = $result['orderId'];
                $shareLink  = $result['shareLink'];

                $track = $this->trackFactory->create();
                $track->setCarrierCode('lalamove');
                $track->setTitle('Lalamove');
                $track->setNumber($lalamoveId);
                $shipment->addTrack($track);
                $this->shipmentRepository->save($shipment);

                $order->addCommentToStatusHistory(
                    __('Lalamove order created. Order ID: %1 | Track: %2', $lalamoveId, $shareLink),
                    false,
                    false
                );
                $this->orderRepository->save($order);

                $this->messageManager->addSuccessMessage(
                    __('Lalamove order created! Tracking: %1', $lalamoveId)
                );

                return $redirect->setPath('adminhtml/order_shipment/view', ['shipment_id' => $shipmentId]);

            } else {
                // --- Called from shipment CREATE page: create shipment then dispatch ---
                $order = $this->orderRepository->get($orderId);

                $result     = $this->lalamoveOrderApi->dispatch($order);
                $lalamoveId = $result['orderId'];
                $shareLink  = $result['shareLink'];

                // Build qty array for all shippable items
                $qtys = [];
                foreach ($order->getAllItems() as $item) {
                    if ($item->getQtyToShip() && !$item->getIsVirtual()) {
                        $qtys[$item->getId()] = $item->getQtyToShip();
                    }
                }

                $shipment = $this->shipmentFactory->create($order, $qtys);
                $shipment->register();

                $track = $this->trackFactory->create();
                $track->setCarrierCode('lalamove');
                $track->setTitle('Lalamove');
                $track->setNumber($lalamoveId);
                $shipment->addTrack($track);
                $this->shipmentRepository->save($shipment);

                // Save Lalamove order ID and comment on the order
                $order->setData('lalamove_order_id', $lalamoveId);
                $order->addCommentToStatusHistory(
                    __('Lalamove order created. Order ID: %1 | Track: %2', $lalamoveId, $shareLink),
                    false,
                    false
                );
                $this->orderRepository->save($order);

                $this->messageManager->addSuccessMessage(
                    __('Lalamove order created! Tracking: %1', $lalamoveId)
                );

                return $redirect->setPath('adminhtml/order_shipment/view', ['shipment_id' => $shipment->getId()]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Lalamove Send error: ' . $e->getMessage(), [
                'shipment_id' => $shipmentId,
                'order_id'    => $orderId,
                'trace'       => $e->getTraceAsString(),
            ]);
            $this->messageManager->addErrorMessage(
                __('Lalamove error: %1', $e->getMessage())
            );
        }

        // Fallback redirect
        if ($shipmentId) {
            return $redirect->setPath('adminhtml/order_shipment/view', ['shipment_id' => $shipmentId]);
        }
        return $redirect->setPath('sales/order/view', ['order_id' => $orderId]);
    }
}
