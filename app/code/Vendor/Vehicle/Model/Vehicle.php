<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Model;

use Magento\Framework\Model\AbstractModel;
use Vendor\Vehicle\Api\Data\VehicleInterface;
use Vendor\Vehicle\Model\ResourceModel\Vehicle as VehicleResource;

class Vehicle extends AbstractModel implements VehicleInterface
{
    protected $_eventPrefix = 'vendor_vehicle';

    protected function _construct(): void
    {
        $this->_init(VehicleResource::class);
    }

    public function getMake(): string
    {
        return (string)$this->getData(self::MAKE);
    }

    public function getModel(): string
    {
        return (string)$this->getData(self::MODEL);
    }

    public function getYearStart(): int
    {
        return (int)$this->getData(self::YEAR_START);
    }

    public function getYearEnd(): ?int
    {
        $val = $this->getData(self::YEAR_END);
        return $val !== null ? (int)$val : null;
    }

    public function getSubmodel(): ?string
    {
        return $this->getData(self::SUBMODEL);
    }

    public function getEngine(): ?string
    {
        return $this->getData(self::ENGINE);
    }

    public function getIsActive(): int
    {
        return (int)$this->getData(self::IS_ACTIVE);
    }

    public function setMake(string $make): self
    {
        return $this->setData(self::MAKE, $make);
    }

    public function setModel(string $model): self
    {
        return $this->setData(self::MODEL, $model);
    }

    public function setYearStart(int $yearStart): self
    {
        return $this->setData(self::YEAR_START, $yearStart);
    }

    public function setYearEnd(?int $yearEnd): self
    {
        return $this->setData(self::YEAR_END, $yearEnd);
    }

    public function setSubmodel(?string $submodel): self
    {
        return $this->setData(self::SUBMODEL, $submodel);
    }

    public function setEngine(?string $engine): self
    {
        return $this->setData(self::ENGINE, $engine);
    }

    public function setIsActive(int $isActive): self
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }
}
