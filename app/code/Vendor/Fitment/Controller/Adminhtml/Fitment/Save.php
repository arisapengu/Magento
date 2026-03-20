<?php

declare(strict_types=1);

namespace Vendor\Fitment\Controller\Adminhtml\Fitment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Vendor\Fitment\Model\FitmentFactory;
use Vendor\Fitment\Model\ResourceModel\Fitment as FitmentResource;

class Save extends Action
{
    /**
     * ACL resource
     */
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @var FitmentFactory
     */
    private FitmentFactory $fitmentFactory;

    /**
     * @var FitmentResource
     */
    private FitmentResource $fitmentResource;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param FitmentFactory $fitmentFactory
     * @param FitmentResource $fitmentResource
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        FitmentFactory $fitmentFactory,
        FitmentResource $fitmentResource
    ) {
        parent::__construct($context);
        $this->jsonFactory    = $jsonFactory;
        $this->fitmentFactory = $fitmentFactory;
        $this->fitmentResource = $fitmentResource;
    }

    /**
     * Execute save action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $productId = (int)$this->getRequest()->getPost('product_id');
        $vehicleId = (int)$this->getRequest()->getPost('vehicle_id');
        $note      = (string)$this->getRequest()->getPost('note', '');

        if (!$productId || !$vehicleId) {
            return $result->setData([
                'success' => false,
                'message' => __('Invalid product or vehicle ID.')->render(),
            ]);
        }

        /* Check for duplicate */
        $existing = $this->fitmentResource->getByProductAndVehicle($productId, $vehicleId);
        if (!empty($existing)) {
            return $result->setData([
                'success' => false,
                'message' => __('This vehicle fitment already exists for the product.')->render(),
            ]);
        }

        try {
            /** @var \Vendor\Fitment\Model\Fitment $fitment */
            $fitment = $this->fitmentFactory->create();
            $fitment->setData([
                'product_id' => $productId,
                'vehicle_id' => $vehicleId,
                'note'       => $note,
            ]);
            $this->fitmentResource->save($fitment);

            /* Fetch vehicle details for the label */
            $connection = $this->fitmentResource->getConnection();
            $select = $connection->select()
                ->from($this->fitmentResource->getTable('vendor_vehicle'), ['make', 'model', 'year_start', 'year_end'])
                ->where('id = ?', $vehicleId);
            $vehicle = $connection->fetchRow($select);

            $vehicleLabel = '';
            if ($vehicle) {
                $yearEnd = !empty($vehicle['year_end']) ? $vehicle['year_end'] : 'present';
                $vehicleLabel = trim(
                    $vehicle['make'] . ' ' . $vehicle['model'] . ' ' . $vehicle['year_start'] . '-' . $yearEnd
                );
            }

            return $result->setData([
                'success'       => true,
                'fitment_id'    => (int)$fitment->getId(),
                'vehicle_label' => $vehicleLabel,
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}