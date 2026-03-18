<?php
namespace Vendor\CompanyAttribute\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OptionImage extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('vendor_attribute_option_image', 'id');
    }

    public function getByOptionId(int $optionId): array
    {
        $connection = $this->getConnection();
        $select     = $connection->select()
            ->from($this->getMainTable())
            ->where('option_id = ?', $optionId);
        return $connection->fetchRow($select) ?: [];
    }

    public function saveOptionImage(int $optionId, string $imagePath): void
    {
        $connection = $this->getConnection();
        $table      = $this->getMainTable();
        $existing   = $this->getByOptionId($optionId);

        if ($existing) {
            $connection->update($table, ['image' => $imagePath], ['option_id = ?' => $optionId]);
        } else {
            $connection->insert($table, ['option_id' => $optionId, 'image' => $imagePath]);
        }
    }

    public function deleteByOptionId(int $optionId): void
    {
        $this->getConnection()->delete($this->getMainTable(), ['option_id = ?' => $optionId]);
    }

    /**
     * Get all option images for a given attribute_id
     * Returns: [ option_id => image_path ]
     */
    public function getImagesByAttributeId(int $attributeId): array
    {
        $connection = $this->getConnection();
        $select     = $connection->select()
            ->from(['img' => $this->getMainTable()], ['option_id', 'image'])
            ->join(
                ['opt' => $this->getTable('eav_attribute_option')],
                'opt.option_id = img.option_id',
                []
            )
            ->where('opt.attribute_id = ?', $attributeId);

        $rows   = $connection->fetchAll($select);
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['option_id']] = $row['image'];
        }
        return $result;
    }
}
