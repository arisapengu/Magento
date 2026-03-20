<?php

declare(strict_types=1);

namespace Vendor\Fitment\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class GetModels extends Action
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
     * Execute action — return distinct models for the given make
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $make = (string)$this->getRequest()->getParam('make', '');

        if (!$make) {
            return $result->setData([]);
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName  = $this->resourceConnection->getTableName('vendor_vehicle');

        $select = $connection->select()
            ->from($tableName, ['model'])
            ->where('make = ?', $make)
            ->where('is_active = ?', 1)
            ->distinct(true)
            ->order('model ASC');

        $models = $connection->fetchCol($select);

        $data = [];
        foreach ($models as $model) {
            $data[] = [
                'value' => $model,
                'label' => $model,
            ];
        }

        return $result->setData($data);
    }
}