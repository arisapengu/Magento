<?php
declare(strict_types=1);
namespace Vendor\FitmentSearch\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;

class GetMakes implements HttpGetActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly ResourceConnection $resource
    ) {}

    public function execute()
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from('vendor_vehicle', ['make'])
            ->where('is_active = 1')
            ->distinct(true)
            ->order('make ASC');

        $makes = $connection->fetchCol($select);

        $result = array_map(fn($make) => ['value' => $make, 'label' => $make], $makes);

        return $this->jsonFactory->create()->setData($result);
    }
}
