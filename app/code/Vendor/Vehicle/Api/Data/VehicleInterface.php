<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Api\Data;

interface VehicleInterface
{
    const ID         = 'id';
    const MAKE       = 'make';
    const MODEL      = 'model';
    const YEAR_START = 'year_start';
    const YEAR_END   = 'year_end';
    const SUBMODEL   = 'submodel';
    const ENGINE     = 'engine';
    const IS_ACTIVE  = 'is_active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function getId();
    public function getMake(): string;
    public function getModel(): string;
    public function getYearStart(): int;
    public function getYearEnd(): ?int;
    public function getSubmodel(): ?string;
    public function getEngine(): ?string;
    public function getIsActive(): int;

    public function setId($id);
    public function setMake(string $make): self;
    public function setModel(string $model): self;
    public function setYearStart(int $yearStart): self;
    public function setYearEnd(?int $yearEnd): self;
    public function setSubmodel(?string $submodel): self;
    public function setEngine(?string $engine): self;
    public function setIsActive(int $isActive): self;
}
