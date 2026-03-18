<?php
namespace Vendor\CompanyAttribute\Plugin\Controller\Adminhtml\Product\Attribute;

use Magento\Catalog\Controller\Adminhtml\Product\Attribute\Save as SaveController;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\ResourceConnection;
use Vendor\CompanyAttribute\Model\ResourceModel\OptionImage as OptionImageResource;

class Save
{
    public function __construct(
        private OptionImageResource $optionImageResource,
        private ResourceConnection  $resourceConnection
    ) {}

    /**
     * After attribute + options are committed, persist option images.
     *
     * POST keys injected by JS:
     *   option_image[option_id_N]  => path  (existing option, N = real option_id)
     *   option_image[option_0]     => path  (new option, rowId from JS counter)
     *   option_image_delete[N]     => 1     (delete image for existing option N)
     *
     * New options are resolved via sort_order because Magento stores
     * option[order][option_0] in the same POST and we can cross-reference
     * the newly-inserted eav_attribute_option rows by (attribute_id, sort_order).
     */
    public function afterExecute(SaveController $subject, ResultInterface $result): ResultInterface
    {
        $request      = $subject->getRequest();
        $optionImages = $request->getParam('option_image', []);
        $deleteImages = $request->getParam('option_image_delete', []);

        if (empty($optionImages) && empty($deleteImages)) {
            return $result;
        }

        $attributeId = (int)$request->getParam('attribute_id');
        $optionOrder = $request->getParam('option', [])['order'] ?? [];

        // ── Deletes ────────────────────────────────────────────────────
        foreach ($deleteImages as $optionId => $flag) {
            $optionId = (int)$optionId;
            if ($optionId) {
                $this->optionImageResource->deleteByOptionId($optionId);
            }
        }

        // ── Saves ──────────────────────────────────────────────────────
        foreach ($optionImages as $rowId => $path) {
            $path = trim((string)$path);
            if ($path === '' || $path === '__delete__') {
                continue;
            }

            if (str_starts_with($rowId, 'option_id_')) {
                // Existing option – rowId already contains the real DB id
                $optionId = (int)str_replace('option_id_', '', $rowId);
                if ($optionId) {
                    $this->optionImageResource->saveOptionImage($optionId, $path);
                }
            } elseif ($attributeId && isset($optionOrder[$rowId])) {
                // New option – resolve via sort_order after DB insert
                $sortOrder = (int)$optionOrder[$rowId];
                $optionId  = $this->resolveOptionIdBySortOrder($attributeId, $sortOrder);
                if ($optionId) {
                    $this->optionImageResource->saveOptionImage($optionId, $path);
                }
            }
        }

        return $result;
    }

    /**
     * Look up the option_id that was just inserted for this attribute + sort_order.
     */
    private function resolveOptionIdBySortOrder(int $attributeId, int $sortOrder): int
    {
        $connection = $this->resourceConnection->getConnection();
        $table      = $this->resourceConnection->getTableName('eav_attribute_option');
        $select     = $connection->select()
            ->from($table, ['option_id'])
            ->where('attribute_id = ?', $attributeId)
            ->where('sort_order = ?', $sortOrder)
            ->limit(1);

        return (int)$connection->fetchOne($select);
    }
}
