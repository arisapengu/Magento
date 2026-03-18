<?php
namespace Vendor\ApiShipping\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;
use Vendor\ApiShipping\Helper\Api\LalamoveApi;

class Lalamove extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'lalamove';
    protected $_isFixed = true;

    // Method codes shown at checkout
    const METHOD_SAMEDAY   = 'sameday';
    const METHOD_WITHIN24  = 'within24';

    private ResultFactory $rateResultFactory;
    private MethodFactory $rateMethodFactory;
    private LalamoveApi $api;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        LalamoveApi $api,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->api = $api;
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->rateResultFactory->create();

        // Ask API for both prices; falls back to config flat rates if API not set up
        $prices = $this->api->getRates($request);

        $sameDayPrice  = $prices['sameday']  ?? (float)$this->getConfigData('price_sameday');
        $within24Price = $prices['within24'] ?? (float)$this->getConfigData('price_within24');

        // Option 1 — Same Day Delivery
        $result->append($this->buildMethod(
            self::METHOD_SAMEDAY,
            __('Same Day Delivery')->render(),
            $sameDayPrice
        ));

        // Option 2 — Within 24 Hours
        $result->append($this->buildMethod(
            self::METHOD_WITHIN24,
            __('Within 24 Hours')->render(),
            $within24Price
        ));

        return $result;
    }

    private function buildMethod(string $methodCode, string $methodTitle, float $price)
    {
        $method = $this->rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($methodCode);
        $method->setMethodTitle($methodTitle);
        $method->setPrice($price);
        $method->setCost($price);
        return $method;
    }

    public function getAllowedMethods(): array
    {
        return [
            self::METHOD_SAMEDAY  => __('Same Day Delivery')->render(),
            self::METHOD_WITHIN24 => __('Within 24 Hours')->render(),
        ];
    }
}
