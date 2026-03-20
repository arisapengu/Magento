<?php
declare(strict_types=1);
namespace Vendor\FitmentSearch\Block\Search;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;

class Form extends Template
{
    public function __construct(
        Context $context,
        private readonly CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
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
