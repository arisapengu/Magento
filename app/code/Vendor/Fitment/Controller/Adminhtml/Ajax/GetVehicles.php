<?php

declare(strict_types=1);

namespace Vendor\Fitment\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class GetVehicles extends Action
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
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->jsonFactory        = $jsonFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Execute action — return vehicles matching make + model
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $make  = (string)$this->getRequest()->getParam('make', '');
        $model = (string)$this->getRequest()->getParam('model', '');

        if (!$make || !$model) {
            return $result->setData([]);
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName  = $this->resourceConnection->getTableName('vendor_vehicle');

        $select = $connection->select()
            ->from($tableName, ['id', 'year_start', 'year_end', 'submodel', 'engine'])
            ->where('make = ?', $make)
            ->where('model = ?', $model)
            ->where('is_active = ?', 1)
            ->order('year_start ASC');

        $vehicles = $connection->fetchAll($select);

        $data = [];
        foreach ($vehicles as $vehicle) {
            $yearEnd = (isset($vehicle['year_end']) && (string)$vehicle['year_end'] !== '')
                ? $vehicle['year_end']
                : 'present';

            $labelParts = [$vehicle['year_start'] . '-' . $yearEnd];

            if (isset($vehicle['submodel']) && trim($vehicle['submodel']) !== '') {
                $labelParts[] = trim($vehicle['submodel']);
            }

            if (isset($vehicle['engine']) && trim($vehicle['engine']) !== '') {
                $labelParts[] = trim($vehicle['engine']);
            }

            $data[] = [
                'value'      => $vehicle['id'],
                'label'      => implode(' ', $labelParts),
                'year_start' => $vehicle['year_start'],
                'year_end'   => $vehicle['year_end'],
                'submodel'   => $vehicle['submodel'] ?? '',
                'engine'     => $vehicle['engine'] ?? '',
            ];
        }

        return $result->setData($data);
    }
}