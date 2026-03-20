<?php

declare(strict_types=1);

namespace Vendor\Fitment\Block\Adminhtml\Product\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Vendor\Fitment\Model\ResourceModel\Fitment as FitmentResource;
use Vendor\Vehicle\Model\ResourceModel\Vehicle as VehicleResource;

class Fitment extends Template implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'Vendor_Fitment::product/tab/fitment.phtml';

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var FitmentResource
     */
    private FitmentResource $fitmentResource;

    /**
     * @var VehicleResource
     */
    private VehicleResource $vehicleResource;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FitmentResource $fitmentResource
     * @param VehicleResource $vehicleResource
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FitmentResource $fitmentResource,
        VehicleResource $vehicleResource,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->fitmentResource = $fitmentResource;
        $this->vehicleResource = $vehicleResource;
        parent::__construct($context, $data);
    }

    /**
     * Get current product from registry
     *
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get existing fitments for the current product
     *
     * @return array
     */
    public function getExistingFitments(): array
    {
        $product = $this->getProduct();
        if (!$product || !$product->getId()) {
            return [];
        }

        return $this->fitmentResource->getFitmentsByProduct((int)$product->getId());
    }

    /**
     * Get distinct makes from active vehicles
     *
     * @return array
     */
    public function getMakes(): array
    {
        $connection = $this->vehicleResource->getConnection();
        $select = $connection->select()
            ->from($this->vehicleResource->getMainTable(), ['make'])
            ->where('is_active = ?', 1)
            ->distinct(true)
            ->order('make ASC');

        return $connection->fetchCol($select);
    }

    /**
     * Get URL for saving fitment
     *
     * @return string
     */
    public function getSaveUrl(): string
    {
        return $this->getUrl('fitment/fitment/save');
    }

    /**
     * Get URL for deleting fitment
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->getUrl('fitment/fitment/delete');
    }

    /**
     * Get URL for fetching models via AJAX
     *
     * @return string
     */
    public function getModelsUrl(): string
    {
        return $this->getUrl('fitment/ajax/getModels');
    }

    /**
     * Get URL for fetching vehicles via AJAX
     *
     * @return string
     */
    public function getVehiclesUrl(): string
    {
        return $this->getUrl('fitment/ajax/getVehicles');
    }

    /**
     * Get tab label
     *
     * @return string
     */
    public function getTabLabel(): string
    {
        return 'Fitment';
    }

    /**
     * Get tab title
     *
     * @return string
     */
    public function getTabTitle(): string
    {
        return 'Fitment';
    }

    /**
     * Check if tab can be shown
     *
     * @return bool
     */
    public function canShowTab(): bool
    {
        return true;
    }

    /**
     * Check if tab is hidden
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return false;
    }

    /**
     * Get tab CSS class
     *
     * @return string|null
     */
    public function getTabClass(): ?string
    {
        return null;
    }

    /**
     * Get tab URL
     *
     * @return string|null
     */
    public function getTabUrl(): ?string
    {
        return null;
    }

    /**
     * Check if tab content is loaded via AJAX
     *
     * @return bool
     */
    public function isAjaxLoaded(): bool
    {
        return false;
    }
}