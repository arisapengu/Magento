<?php
declare(strict_types=1);

namespace Vendor\FitmentSearch\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;

class GetWiperHooks implements HttpGetActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly ProductAttributeRepositoryInterface $attributeRepository
    ) {}

    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $attribute = $this->attributeRepository->get('wiper_hook');
            $options = [];
            foreach ($attribute->getOptions() as $option) {
                if ($option->getValue() === '') continue;
                $options[] = ['value' => $option->getValue(), 'label' => $option->getLabel()];
            }
            $result->setData(['success' => true, 'options' => $options]);
        } catch (\Exception $e) {
            $result->setData(['success' => false, 'options' => []]);
        }
        return $result;
    }
}
