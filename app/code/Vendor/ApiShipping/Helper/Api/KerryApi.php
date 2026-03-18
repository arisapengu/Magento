<?php
namespace Vendor\ApiShipping\Helper\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;

class KerryApi
{
    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get shipping rate from Kerry Express API.
     * Returns null when API is not configured — carrier will fall back to flat rate.
     *
     * TODO: Implement with real Kerry API credentials.
     * API docs: https://www.kerryexpress.com (merchant portal)
     */
    public function getRate(RateRequest $request): ?float
    {
        $apiUrl = $this->scopeConfig->getValue('carriers/kerry/api_url');
        $apiKey = $this->scopeConfig->getValue('carriers/kerry/api_key');

        if (empty($apiUrl) || empty($apiKey)) {
            return null; // fall back to flat rate
        }

        // TODO: build and send HTTP request to Kerry API
        // $weight      = $request->getPackageWeight();
        // $destCountry = $request->getDestCountryId();
        // $destPostcode = $request->getDestPostcode();

        return null;
    }
}
