<?php
namespace Vendor\CompanyAttribute\Controller\Adminhtml\Option;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Vendor\CompanyAttribute\Model\ResourceModel\OptionImage as OptionImageResource;

class SaveImages extends Action
{
    const ADMIN_RESOURCE = 'Magento_Catalog::attributes_attributes';

    private JsonFactory         $jsonFactory;
    private OptionImageResource $optionImageResource;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        OptionImageResource $optionImageResource
    ) {
        parent::__construct($context);
        $this->jsonFactory         = $jsonFactory;
        $this->optionImageResource = $optionImageResource;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $data   = $this->getRequest()->getPostValue('option_images', []);
        // $data = [ option_id => image_path, ... ]

        try {
            foreach ($data as $optionId => $imagePath) {
                $optionId = (int) $optionId;
                if (!$optionId) continue;

                if ($imagePath === '__delete__') {
                    $this->optionImageResource->deleteByOptionId($optionId);
                } elseif ($imagePath) {
                    $this->optionImageResource->saveOptionImage($optionId, $imagePath);
                }
            }
            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
