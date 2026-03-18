<?php
namespace Vendor\ApiShipping\Preference\Catalog;

use Magento\Catalog\Model\Category as CategoryModel;

class CategoriesOptions extends \Magento\Catalog\Ui\Component\Product\Form\Categories\Options
{
    protected function getCategoriesTree()
    {
        if ($this->categoriesTree === null) {
            $storeId = $this->request->getParam('store');

            $matchingNamesCollection = $this->categoryCollectionFactory->create();
            $matchingNamesCollection->addAttributeToSelect('path')
                ->addAttributeToFilter('entity_id', ['neq' => CategoryModel::TREE_ROOT_ID])
                ->setStoreId($storeId);

            $shownCategoriesIds = [];
            foreach ($matchingNamesCollection as $category) {
                foreach (explode('/', $category->getPath() ?? '') as $parentId) {
                    $shownCategoriesIds[$parentId] = 1;
                }
            }

            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToFilter('entity_id', ['in' => array_keys($shownCategoriesIds)])
                ->addAttributeToSelect(['name', 'is_active', 'parent_id'])
                ->setStoreId($storeId);

            $categoryById = [
                CategoryModel::TREE_ROOT_ID => ['value' => CategoryModel::TREE_ROOT_ID],
            ];

            foreach ($collection as $category) {
                foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                    if (!isset($categoryById[$categoryId])) {
                        $categoryById[$categoryId] = ['value' => $categoryId];
                    }
                }
                $categoryById[$category->getId()]['is_active'] = $category->getIsActive();
                $categoryById[$category->getId()]['label']     = $category->getName();
                $categoryById[$category->getParentId()]['optgroup'][] = &$categoryById[$category->getId()];
            }

            // Fix: use ?? [] to avoid "Undefined array key optgroup" warning on PHP 8
            $this->categoriesTree = $categoryById[CategoryModel::TREE_ROOT_ID]['optgroup'] ?? [];
        }

        return $this->categoriesTree;
    }
}
