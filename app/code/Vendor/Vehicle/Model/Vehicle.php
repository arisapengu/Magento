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

    public function getModelCode(): ?string
    {
        return $this->getData(self::MODEL_CODE);
    }

    public function getModelGen(): ?string
    {
        return $this->getData(self::MODEL_GEN);
    }

    public function getEngineCode(): ?string
    {
        return $this->getData(self::ENGINE_CODE);
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

    public function setModelCode(?string $modelCode): self
    {
        return $this->setData(self::MODEL_CODE, $modelCode);
    }

    public function setModelGen(?string $modelGen): self
    {
        return $this->setData(self::MODEL_GEN, $modelGen);
    }

    public function setEngineCode(?string $engineCode): self
    {
        return $this->setData(self::ENGINE_CODE, $engineCode);
    }

    public function setIsActive(int $isActive): self
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }
}
