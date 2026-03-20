<?php
declare(strict_types=1);
namespace Vendor\FitmentSearch\Controller\Search;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class Result implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly PageFactory $pageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly CustomerSession $customerSession,
        private readonly ResourceConnection $resource,
        private readonly ProductCollectionFactory $productCollectionFactory
    ) {}

    public function execute()
    {
        $make  = trim((string)$this->request->getParam('make'));
        $model = trim((string)$this->request->getParam('model'));
        $year  = (int)$this->request->getParam('year');

        if (!$make || !$model || !$year) {
            return $this->redirectFactory->create()->setPath('fitment/search/form');
        }

        // Save selection to session
        $this->customerSession->setFitmentSelectedVehicle([
            'make'  => $make,
            'model' => $model,
            'year'  => $year,
            'label' => "$make $model $year",
        ]);

        // Find matching vehicle IDs
        $connection = $this->resource->getConnection();
        $vehicleIds = $connection->fetchCol(
            $connection->select()
                ->from('vendor_vehicle', ['id'])
                ->where('make = ?', $make)
                ->where('model = ?', $model)
                ->where('is_active = 1')
                ->where('year_start <= ?', $year)
                ->where('year_end IS NULL OR year_end >= ?', $year)
        );

        // Find product IDs from fitment table
        $productIds = [];
        if (!empty($vehicleIds)) {
            $productIds = $connection->fetchCol(
                $connection->select()
                    ->from('vendor_product_fitment', ['product_id'])
                    ->where('vehicle_id IN (?)', $vehicleIds)
                    ->distinct(true)
            );
        }

        // Build product collection
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'price', 'small_image', 'url_key', 'short_description']);
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('visibility', ['in' => [
            Visibility::VISIBILITY_IN_CATALOG,
            Visibility::VISIBILITY_IN_SEARCH,
            Visibility::VISIBILITY_BOTH,
        ]]);

        if (!empty($productIds)) {
            $collection->addAttributeToFilter('entity_id', ['in' => $productIds]);
        } else {
            $collection->addAttributeToFilter('entity_id', ['in' => [0]]);
        }

        $collection->setPageSize(24);
        $collection->load();

        $page = $this->pageFactory->create();
        $block = $page->getLayout()->getBlock('fitment_search_result');
        if ($block) {
            $block->setData('products', $collection);
            $block->setData('make', $make);
            $block->setData('model', $model);
            $block->setData('year', $year);
        }

        return $page;
    }
}
