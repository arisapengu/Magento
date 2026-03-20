<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Model\Vehicle;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Vendor\Vehicle\Model\ResourceModel\Vehicle\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $vehicle) {
            $this->loadedData[$vehicle->getId()] = $vehicle->getData();
        }

        return $this->loadedData;
    }
}
