<?php

declare(strict_types=1);

namespace Vendor\Fitment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Fitment extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('vendor_product_fitment', 'id');
    }

    /**
     * Get fitment record by product and vehicle IDs
     *
     * @param int $productId
     * @param int $vehicleId
     * @return array
     */
    public function getByProductAndVehicle(int $productId, int $vehicleId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('product_id = ?', $productId)
            ->where('vehicle_id = ?', $vehicleId);

        $result = $connection->fetchRow($select);

        return $result !== false ? $result : [];
    }

    /**
     * Get all fitments for a product with vehicle details
     *
     * @param int $productId
     * @return array
     */
    public function getFitmentsByProduct(int $productId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['f' => $this->getMainTable()], ['id', 'vehicle_id', 'note'])
            ->join(
                ['v' => $this->getTable('vendor_vehicle')],
                'f.vehicle_id = v.id',
                ['make', 'model', 'year_start', 'year_end', 'submodel', 'engine']
            )
            ->where('f.product_id = ?', $productId)
            ->order(['v.make ASC', 'v.model ASC', 'v.year_start ASC']);

        return $connection->fetchAll($select);
    }
}