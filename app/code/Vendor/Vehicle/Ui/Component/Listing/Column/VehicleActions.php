<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class VehicleActions extends Column
{
    private const URL_PATH_EDIT   = 'vehicle/vehicle/edit';
    private const URL_PATH_DELETE = 'vehicle/vehicle/delete';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $name = $this->getData('name');
            $id   = $item['id'];

            $item[$name] = [
                'edit' => [
                    'href'  => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['id' => $id]),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href'    => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['id' => $id]),
                    'label'   => __('Delete'),
                    'confirm' => [
                        'title'   => __('Delete Vehicle'),
                        'message' => __('Are you sure you want to delete this vehicle?'),
                    ],
                    'post' => true,
                ],
            ];
        }

        return $dataSource;
    }
}
