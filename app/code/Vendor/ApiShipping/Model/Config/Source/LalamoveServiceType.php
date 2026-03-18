<?php
namespace Vendor\ApiShipping\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LalamoveServiceType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'MOTORCYCLE', 'label' => 'Motorcycle (most common, small parcels)'],
            ['value' => 'SEDAN',      'label' => 'Car / Sedan (medium parcels)'],
            ['value' => 'MPV',        'label' => 'MPV / Van (large parcels)'],
            ['value' => 'TRUCK175',   'label' => 'Pickup Truck 1.75T'],
            ['value' => 'TRUCK330',   'label' => 'Pickup Truck 3.3T'],
        ];
    }
}
