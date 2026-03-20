<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Vehicle extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('vendor_vehicle', 'id');
    }

    /**
     * Get distinct makes for autocomplete
     */
    public function getDistinctMakes(): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['make'])
            ->distinct(true)
            ->order('make ASC');

        return $connection->fetchCol($select);
    }

    /**
     * Bulk insert vehicles
     */
    public function bulkInsert(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }
        $this->getConnection()->insertMultiple($this->getMainTable(), $rows);
        return count($rows);
    }
}
