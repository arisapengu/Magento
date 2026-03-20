<?php
declare(strict_types=1);
namespace Vendor\FitmentSearch\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;

class GetYears implements HttpGetActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly ResourceConnection $resource,
        private readonly RequestInterface $request
    ) {}

    public function execute()
    {
        $make  = trim((string)$this->request->getParam('make'));
        $model = trim((string)$this->request->getParam('model'));

        if (!$make || !$model) {
            return $this->jsonFactory->create()->setData([]);
        }

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from('vendor_vehicle', ['year_start', 'year_end'])
            ->where('make = ?', $make)
            ->where('model = ?', $model)
            ->where('is_active = 1');

        $rows = $connection->fetchAll($select);

        $years = [];
        foreach ($rows as $row) {
            $start = (int)$row['year_start'];
            $end   = $row['year_end'] ? (int)$row['year_end'] : (int)date('Y');
            for ($y = $start; $y <= $end; $y++) {
                $years[$y] = $y;
            }
        }

        krsort($years);

        $result = array_values(array_map(fn($y) => ['value' => $y, 'label' => (string)$y], $years));

        return $this->jsonFactory->create()->setData($result);
    }
}
