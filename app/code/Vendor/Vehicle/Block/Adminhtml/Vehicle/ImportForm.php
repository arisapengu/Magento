<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Block\Adminhtml\Vehicle;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class ImportForm extends Template
{
    protected $_template = 'Vendor_Vehicle::vehicle/import_form.phtml';

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function getImportUrl(): string
    {
        return $this->getUrl('vehicle/vehicle/importPost');
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('vehicle/vehicle/index');
    }
}
