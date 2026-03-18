<?php
namespace Vendor\ApiShipping\Helper\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Log\LoggerInterface;

class LalamoveOrderApi
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
     * Full flow: Quotation → Order
     *
     * Returns ['orderId' => string, 'shareLink' => string] or throws exception on failure.
     */
    public function dispatch(\Magento\Sales\Model\Order $order): array
    {
        $apiUrl      = $this->scopeConfig->getValue('carriers/lalamove/api_url');
        $apiKey      = trim($this->encryptor->decrypt($this->scopeConfig->getValue('carriers/lalamove/api_key')));
        $apiSecret   = trim($this->encryptor->decrypt($this->scopeConfig->getValue('carriers/lalamove/api_secret')));
        $market      = $this->scopeConfig->getValue('carriers/lalamove/market') ?: 'TH';
        $serviceType = $this->scopeConfig->getValue('carriers/lalamove/service_type') ?: 'MOTORCYCLE';
        $storeLat    = $this->scopeConfig->getValue('carriers/lalamove/store_lat');
        $storeLng    = $this->scopeConfig->getValue('carriers/lalamove/store_lng');
        $storeAddr   = $this->scopeConfig->getValue('carriers/lalamove/store_address');
        $senderName  = $this->scopeConfig->getValue('carriers/lalamove/sender_name');
        $senderPhone = $this->scopeConfig->getValue('carriers/lalamove/sender_phone');

        // --- Validate required config ---
        $missing = [];
        if (empty($apiKey))      $missing[] = 'API Key';
        if (empty($apiSecret))   $missing[] = 'API Secret';
        if (empty($storeLat))    $missing[] = 'Store Latitude';
        if (empty($storeLng))    $missing[] = 'Store Longitude';
        if (empty($senderName))  $missing[] = 'Sender Name';
        if (empty($senderPhone)) $missing[] = 'Sender Phone';

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Lalamove config missing: ' . implode(', ', $missing) .
                '. Please fill in Admin → Stores → Config → Delivery Methods → Lalamove.'
            );
        }

        // --- Geocode customer delivery address ---
        $shipping   = $order->getShippingAddress();
        $street     = implode(' ', $shipping->getStreet());
        $city       = $shipping->getCity();
        $postcode   = $shipping->getPostcode();
        $countryId  = $shipping->getCountryId();

        $this->logger->info('Lalamove dispatch: geocoding address', [
            'street'   => $street,
            'city'     => $city,
            'postcode' => $postcode,
            'country'  => $countryId,
        ]);

        $destCoords = $this->geocoder->getCoordinates($street, $city, $postcode, $countryId);

        if (!$destCoords) {
            throw new \RuntimeException(
                'Cannot geocode customer address: "' . implode(', ', array_filter([$street, $city, $postcode])) . '". ' .
                'Please check address or configure Google Maps API key.'
            );
        }

        $this->logger->info('Lalamove dispatch: geocode result', $destCoords);

        $destAddr = trim(implode(', ', array_filter([
            implode(' ', $shipping->getStreet()),
            $shipping->getCity(),
            $shipping->getPostcode(),
        ])));

        $stops = [
            [
                'coordinates' => ['lat' => (string)$storeLat, 'lng' => (string)$storeLng],
                'address'     => (string)$storeAddr,
            ],
            [
                'coordinates' => ['lat' => $destCoords['lat'], 'lng' => $destCoords['lng']],
                'address'     => $destAddr,
            ],
        ];

        // Step 1: Get fresh quotation (returns quotationId + real stopIds)
        $quotation = $this->getQuotation($apiUrl, $apiKey, $apiSecret, $market, $serviceType, $stops);

        // Step 2: Place order using quotationId and real stopIds from quotation
        $result = $this->placeOrder(
            $apiUrl, $apiKey, $apiSecret, $market,
            $quotation['quotationId'],
            $quotation['senderStopId'],
            $quotation['recipientStopId'],
            $senderName, $senderPhone,
            $shipping->getName(), $shipping->getTelephone(),
            'Order #' . $order->getIncrementId()
        );

        return $result;
    }

    /**
     * POST /v3/quotations → return ['quotationId' => string, 'stopIds' => [senderStopId, recipientStopId]].
     */
    private function getQuotation(
        string $apiUrl, string $apiKey, string $apiSecret,
        string $market, string $serviceType, array $stops
    ): array {
        $body = json_encode([
            'data' => [
                'serviceType' => $serviceType,
                'language'    => 'en_TH',
                'stops'       => $stops,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response    = $this->callApi($apiUrl, $apiKey, $apiSecret, $market, 'POST', '/v3/quotations', $body, 201);
        $quotationId = $response['data']['quotationId'] ?? null;

        if (!$quotationId) {
            throw new \RuntimeException('Lalamove: quotationId missing in response.');
        }

        // Extract the real stopIds assigned by Lalamove (required for /v3/orders)
        $responseStops = $response['data']['stops'] ?? [];
        $senderStopId    = $responseStops[0]['stopId'] ?? null;
        $recipientStopId = $responseStops[1]['stopId'] ?? null;

        if (!$senderStopId || !$recipientStopId) {
            throw new \RuntimeException('Lalamove: stopId missing in quotation response.');
        }

        return [
            'quotationId'    => $quotationId,
            'senderStopId'   => $senderStopId,
            'recipientStopId'=> $recipientStopId,
        ];
    }

    /**
     * POST /v3/orders → return ['orderId' => ..., 'shareLink' => ...].
     */
    private function placeOrder(
        string $apiUrl, string $apiKey, string $apiSecret, string $market,
        string $quotationId,
        string $senderStopId, string $recipientStopId,
        string $senderName, string $senderPhone,
        string $recipientName, string $recipientPhone,
        string $remarks
    ): array {
        $body = json_encode([
            'data' => [
                'quotationId'           => $quotationId,
                'sender'                => [
                    'stopId' => $senderStopId,
                    'name'   => $senderName,
                    'phone'  => $senderPhone,
                ],
                'recipients'            => [
                    [
                        'stopId'  => $recipientStopId,
                        'name'    => $recipientName,
                        'phone'   => $recipientPhone,
                        'remarks' => $remarks,
                    ],
                ],
                'isPODEnabled'          => false,
                'isRecipientSMSEnabled' => true,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response  = $this->callApi($apiUrl, $apiKey, $apiSecret, $market, 'POST', '/v3/orders', $body, 201);
        $orderId   = $response['data']['orderId']   ?? null;
        $shareLink = $response['data']['shareLink'] ?? '';

        if (!$orderId) {
            throw new \RuntimeException('Lalamove: orderId missing in response.');
        }

        return ['orderId' => $orderId, 'shareLink' => $shareLink];
    }

    /**
     * Generic signed API call with HMAC-SHA256 auth.
     */
    private function callApi(
        string $apiUrl, string $apiKey, string $apiSecret,
        string $market, string $method, string $path,
        string $body, int $expectedStatus
    ): array {
        $timestamp    = (string)(int)(microtime(true) * 1000);
        $requestId    = $this->uuid4();
        $rawSignature = "{$timestamp}\r\n{$method}\r\n{$path}\r\n\r\n{$body}";
        $signature    = hash_hmac('sha256', $rawSignature, $apiSecret);
        $token        = "{$apiKey}:{$timestamp}:{$signature}";

        // Log request for debugging
        $this->logger->info("Lalamove → {$method} {$path}", ['body' => $body]);

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

        $this->logger->info("Lalamove ← [{$statusCode}] {$path}", ['response' => $responseBody]);

        if ($statusCode !== $expectedStatus) {
            $this->logger->error("Lalamove {$path} failed [{$statusCode}]: {$responseBody}");
            throw new \RuntimeException("Lalamove API error [{$statusCode}]: {$responseBody}");
        }

        return json_decode($responseBody, true);
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
