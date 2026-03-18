<?php
namespace Vendor\ApiShipping\Helper\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;

class FlashApi
{
    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get shipping rate from Flash Express API.
     * Returns null when API is not configured — carrier will fall back to flat rate.
     *
     * TODO: Implement with real Flash Express API credentials.
     * API docs: Flash merchant portal (https://www.flashexpress.co.th)
     */
    public function getRate(RateRequest $request): ?float
    {
        $apiUrl = $this->scopeConfig->getValue('carriers/flash/api_url');
        $apiKey = $this->scopeConfig->getValue('carriers/flash/api_key');

        if (empty($apiUrl) || empty($apiKey)) {
            return null; // fall back to flat rate
        }

        // TODO: build and send HTTP request to Flash Express API
        // $weight      = $request->getPackageWeight();
        // $destCountry = $request->getDestCountryId();
        // $destPostcode = $request->getDestPostcode();

        return null;
    }
}
