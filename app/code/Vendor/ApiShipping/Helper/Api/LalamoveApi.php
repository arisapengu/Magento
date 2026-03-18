<?php
namespace Vendor\ApiShipping\Helper\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Psr\Log\LoggerInterface;

class LalamoveApi
{
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;
    private Geocoder $geocoder;
    private EncryptorInterface $encryptor;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Geocoder $geocoder,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger      = $logger;
        $this->geocoder    = $geocoder;
        $this->encryptor   = $encryptor;
    }

    /**
     * Returns ['sameday' => float, 'within24' => float] or [] on failure/unconfigured.
     *
     * Flow:
     *  1. Geocode customer address → lat/lng  (Google Maps, cached 24h)
     *  2. POST /v3/quotations to Lalamove
     *  3. Return priceBreakdown.total for both Same Day and Within 24 Hours
     */
    public function getRates(RateRequest $request): array
    {
        $apiUrl      = $this->scopeConfig->getValue('carriers/lalamove/api_url');
        $apiKey      = trim($this->encryptor->decrypt($this->scopeConfig->getValue('carriers/lalamove/api_key')));
        $apiSecret   = trim($this->encryptor->decrypt($this->scopeConfig->getValue('carriers/lalamove/api_secret')));
        $market      = $this->scopeConfig->getValue('carriers/lalamove/market') ?: 'TH';
        $serviceType = $this->scopeConfig->getValue('carriers/lalamove/service_type') ?: 'MOTORCYCLE';
        $storeLat    = $this->scopeConfig->getValue('carriers/lalamove/store_lat');
        $storeLng    = $this->scopeConfig->getValue('carriers/lalamove/store_lng');
        $storeAddr   = $this->scopeConfig->getValue('carriers/lalamove/store_address');

        if (empty($apiUrl) || empty($apiKey) || empty($apiSecret) || empty($storeLat) || empty($storeLng)) {
            return []; // not fully configured — carrier uses flat-rate fallback
        }

        // --- Geocode customer destination ---
        $destStreet   = $request->getDestStreet() ?? '';
        $destCity     = $request->getDestCity() ?? '';
        $destPostcode = $request->getDestPostcode() ?? '';
        $destCountry  = $request->getDestCountryId() ?? 'TH';

        $destCoords = $this->geocoder->getCoordinates($destStreet, $destCity, $destPostcode, $destCountry);

        if ($destCoords === null) {
            // Geocoding failed or not configured — fall back to flat rate
            $this->logger->info('Lalamove: geocoding failed, using flat rate fallback.');
            return [];
        }

        // Strip newlines from address fields (Magento stores multi-line streets with \n)
        $cleanStreet = preg_replace('/[\r\n]+/', ' ', trim($destStreet));
        $destAddr    = trim(implode(', ', array_filter([$cleanStreet, $destCity, $destPostcode])));

        // --- Build Lalamove quotation body ---
        $body = json_encode([
            'data' => [
                'serviceType' => $serviceType,
                'language'    => 'en_TH',
                'stops'       => [
                    [
                        'coordinates' => ['lat' => (string)$storeLat, 'lng' => (string)$storeLng],
                        'address'     => (string)$storeAddr,
                    ],
                    [
                        'coordinates' => ['lat' => $destCoords['lat'], 'lng' => $destCoords['lng']],
                        'address'     => $destAddr,
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $price = $this->postQuotation($apiUrl, $apiKey, $apiSecret, $market, $body);

        if ($price === null) {
            return [];
        }

        // Lalamove returns one price regardless of schedule time.
        // Both "Same Day" and "Within 24 Hours" display this live price.
        return [
            'sameday'  => $price,
            'within24' => $price,
        ];
    }

    /**
     * POST /v3/quotations → return total price float, or null on error.
     *
     * Auth: HMAC-SHA256
     *   Signature : HMAC-SHA256("<TIMESTAMP>\r\nPOST\r\n/v3/quotations\r\n\r\n<BODY>", SECRET)
     *   Header    : Authorization: hmac <API_KEY>:<TIMESTAMP>:<SIGNATURE>
     *   Header    : Market: TH
     *   Header    : Request-ID: <unique UUID per request>
     */
    private function postQuotation(
        string $apiUrl,
        string $apiKey,
        string $apiSecret,
        string $market,
        string $body
    ): ?float {
        $path      = '/v3/quotations';
        $timestamp = (string)(int)(microtime(true) * 1000);
        $requestId = $this->uuid4();

        $rawSignature = "{$timestamp}\r\nPOST\r\n{$path}\r\n\r\n{$body}";
        $signature    = hash_hmac('sha256', $rawSignature, $apiSecret);
        $token        = "{$apiKey}:{$timestamp}:{$signature}";

        $this->logger->info('Lalamove checkout quotation request', [
            'body'      => $body,
            'timestamp' => $timestamp,
            'token'     => $token,
            'raw_sig'   => str_replace("\r\n", "\\r\\n", $rawSignature),
        ]);

        try {
            $ch = curl_init($apiUrl . $path);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: hmac ' . $token,
                    'Market: ' . $market,
                    'Request-ID: ' . $requestId,
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($body),
                ],
            ]);
            $responseBody = curl_exec($ch);
            $statusCode   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($statusCode !== 201) {
                $this->logger->warning("Lalamove quotation failed [{$statusCode}]: {$responseBody}");
                return null;
            }

            $data  = json_decode($responseBody, true);
            $total = $data['data']['priceBreakdown']['total'] ?? null;

            if ($total === null) {
                $this->logger->warning('Lalamove: priceBreakdown.total missing in response.');
                return null;
            }

            return (float)$total;

        } catch (\Exception $e) {
            $this->logger->error('Lalamove API exception: ' . $e->getMessage());
            return null;
        }
    }

    private function uuid4(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
