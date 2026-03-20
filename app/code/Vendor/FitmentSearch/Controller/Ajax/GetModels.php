<?php
declare(strict_types=1);
namespace Vendor\FitmentSearch\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;

class GetModels implements HttpGetActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly ResourceConnection $resource,
        private readonly RequestInterface $request
    ) {}

    public function execute()
    {
        $make = trim((string)$this->request->getParam('make'));

        if (!$make) {
            return $this->jsonFactory->create()->setData([]);
        }

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from('vendor_vehicle', ['model'])
            ->where('make = ?', $make)
            ->where('is_active = 1')
            ->distinct(true)
            ->order('model ASC');

        $models = $connection->fetchCol($select);

        $result = array_map(fn($model) => ['value' => $model, 'label' => $model], $models);

        return $this->jsonFactory->create()->setData($result);
    }
}
