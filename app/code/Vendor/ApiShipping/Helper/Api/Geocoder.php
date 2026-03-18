<?php
namespace Vendor\ApiShipping\Helper\Api;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class Geocoder
{
    private const CACHE_TTL      = 86400; // 24 hours
    private const CACHE_TAG      = 'vendor_apishipping_geocode';
    private const NOMINATIM_URL  = 'https://nominatim.openstreetmap.org/search';
    private const GMAPS_URL      = 'https://maps.googleapis.com/maps/api/geocode/json';

    private ScopeConfigInterface $scopeConfig;
    private Curl $curl;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->curl        = $curl;
        $this->cache       = $cache;
        $this->logger      = $logger;
    }

    /**
     * Convert address → ['lat' => string, 'lng' => string] or null.
     *
     * Priority:
     *  1. Google Maps API  — if gmaps_key is configured in admin
     *  2. OpenStreetMap Nominatim — free, no API key needed (fallback)
     *
     * Results are cached 24 hours so same postcode never calls twice.
     */
    public function getCoordinates(string $street, string $city, string $postcode, string $country = 'TH'): ?array
    {
        $cacheKey = self::CACHE_TAG . '_' . md5("{$street}|{$city}|{$postcode}|{$country}");
        $cached   = $this->cache->load($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        $gmapsKey = $this->scopeConfig->getValue('carriers/lalamove/gmaps_key');

        // Build candidate queries from specific → broad
        // Lalamove only needs approximate location for routing, so postcode+city is good enough
        $candidates = array_filter([
            trim(implode(', ', array_filter([$street, $city, $postcode, $country]))),  // full address
            trim(implode(', ', array_filter([$city, $postcode, $country]))),            // city + postcode
            trim(implode(', ', array_filter([$postcode, $country]))),                   // postcode only
        ]);

        $coords = null;
        foreach ($candidates as $address) {
            $coords = !empty($gmapsKey)
                ? $this->geocodeWithGoogle($address, $country, $gmapsKey)
                : $this->geocodeWithNominatim($address, $country);

            if ($coords !== null) {
                $this->logger->info("Geocoder: found coords for \"{$address}\"", $coords);
                break;
            }
        }

        if ($coords !== null) {
            $this->cache->save(json_encode($coords), $cacheKey, [self::CACHE_TAG], self::CACHE_TTL);
        }

        return $coords;
    }

    /**
     * OpenStreetMap Nominatim — free, no API key required.
     * Rate limit: 1 req/sec (caching handles this in practice).
     */
    private function geocodeWithNominatim(string $address, string $country): ?array
    {
        $url = self::NOMINATIM_URL . '?' . http_build_query([
            'q'              => $address,
            'format'         => 'json',
            'limit'          => 1,
            'countrycodes'   => strtolower($country), // restrict to Thailand = 'th'
            'addressdetails' => 0,
        ]);

        try {
            // Nominatim requires a User-Agent header identifying your app
            $this->curl->setHeaders([
                'User-Agent' => 'Vendor_ApiShipping/1.0 Magento2 (contact@yourstore.com)',
                'Accept'     => 'application/json',
            ]);
            $this->curl->get($url);

            $statusCode = $this->curl->getStatus();
            $body       = $this->curl->getBody();

            if ($statusCode !== 200) {
                $this->logger->warning("Nominatim HTTP [{$statusCode}] for: {$address}");
                return null;
            }

            $results = json_decode($body, true);

            if (empty($results[0]['lat']) || empty($results[0]['lon'])) {
                $this->logger->info("Nominatim: no result for: {$address}");
                return null;
            }

            return [
                'lat' => (string)$results[0]['lat'],
                'lng' => (string)$results[0]['lon'], // Nominatim uses 'lon', Lalamove uses 'lng'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Nominatim exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Google Maps Geocoding API — requires API key from Google Cloud Console.
     * Enable "Geocoding API" in your project.
     */
    private function geocodeWithGoogle(string $address, string $country, string $apiKey): ?array
    {
        $url = self::GMAPS_URL . '?' . http_build_query([
            'address' => $address,
            'key'     => $apiKey,
            'region'  => strtolower($country),
        ]);

        try {
            $this->curl->setHeaders(['Accept' => 'application/json']);
            $this->curl->get($url);

            $statusCode = $this->curl->getStatus();
            $body       = $this->curl->getBody();

            if ($statusCode !== 200) {
                $this->logger->warning("Google Maps HTTP [{$statusCode}] for: {$address}");
                return null;
            }

            $data     = json_decode($body, true);
            $status   = $data['status'] ?? 'UNKNOWN';
            $location = $data['results'][0]['geometry']['location'] ?? null;

            if ($status !== 'OK' || !$location) {
                $this->logger->warning("Google Maps status [{$status}] for: {$address}");
                return null;
            }

            return [
                'lat' => (string)$location['lat'],
                'lng' => (string)$location['lng'],
            ];

        } catch (\Exception $e) {
            $this->logger->error('Google Maps Geocoder exception: ' . $e->getMessage());
            return null;
        }
    }
}
