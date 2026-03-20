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
    const MODEL_CODE  = 'model_code';
    const MODEL_GEN   = 'model_gen';
    const ENGINE_CODE = 'engine_code';
    const IS_ACTIVE   = 'is_active';
    const CREATED_AT  = 'created_at';
    const UPDATED_AT  = 'updated_at';

    public function getId();
    public function getMake(): string;
    public function getModel(): string;
    public function getYearStart(): int;
    public function getYearEnd(): ?int;
    public function getModelCode(): ?string;
    public function getModelGen(): ?string;
    public function getEngineCode(): ?string;
    public function getIsActive(): int;

    public function setId($id);
    public function setMake(string $make): self;
    public function setModel(string $model): self;
    public function setYearStart(int $yearStart): self;
    public function setYearEnd(?int $yearEnd): self;
    public function setModelCode(?string $modelCode): self;
    public function setModelGen(?string $modelGen): self;
    public function setEngineCode(?string $engineCode): self;
    public function setIsActive(int $isActive): self;
}
