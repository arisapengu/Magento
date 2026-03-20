<?php

declare(strict_types=1);

namespace Vendor\Fitment\Controller\Adminhtml\Fitment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Vendor\Fitment\Model\FitmentFactory;
use Vendor\Fitment\Model\ResourceModel\Fitment as FitmentResource;

class Delete extends Action
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
        $this->jsonFactory     = $jsonFactory;
        $this->fitmentFactory  = $fitmentFactory;
        $this->fitmentResource = $fitmentResource;
    }

    /**
     * Execute delete action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $fitmentId = (int)$this->getRequest()->getPost('fitment_id');

        if (!$fitmentId) {
            return $result->setData([
                'success' => false,
                'message' => __('Invalid fitment ID.')->render(),
            ]);
        }

        /** @var \Vendor\Fitment\Model\Fitment $fitment */
        $fitment = $this->fitmentFactory->create();
        $this->fitmentResource->load($fitment, $fitmentId);

        if (!$fitment->getId()) {
            return $result->setData([
                'success' => false,
                'message' => __('Fitment record not found.')->render(),
            ]);
        }

        try {
            $this->fitmentResource->delete($fitment);

            return $result->setData([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}