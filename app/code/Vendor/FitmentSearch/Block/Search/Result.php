<?php
declare(strict_types=1);
namespace Vendor\FitmentSearch\Block\Search;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Catalog\Helper\Image as ImageHelper;

class Result extends Template
{
    public function __construct(
        Context $context,
        private readonly CustomerSession $customerSession,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly ImageHelper $imageHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getProducts(): ?ProductCollection
    {
        return $this->getData('products');
    }

    public function getVehicleLabel(): string
    {
        $make  = (string)$this->getData('make');
        $model = (string)$this->getData('model');
        $year  = (string)$this->getData('year');
        return trim("$make $model $year");
    }

    public function getProductUrl(Product $product): string
    {
        return $product->getProductUrl();
    }

    public function getProductImageUrl(Product $product): string
    {
        return $this->imageHelper->init($product, 'product_page_image_small')
            ->setImageFile($product->getSmallImage())
            ->getUrl();
    }

    public function getFormattedPrice(float $price): string
    {
        return $this->priceCurrency->format($price);
    }

    public function getFormUrl(): string
    {
        return $this->getUrl('fitment/search/form');
    }

    public function getAjaxUrl(string $action): string
    {
        return $this->getUrl('fitment/ajax/' . $action);
    }

    public function getSearchUrl(): string
    {
        return $this->getUrl('fitment/search/result');
    }

    public function getClearUrl(): string
    {
        return $this->getUrl('fitment/ajax/clearVehicle');
    }

    public function getSelectedVehicle(): ?array
    {
        return $this->customerSession->getFitmentSelectedVehicle() ?: null;
    }
}
