<?php
namespace Vendor\CompanyAttribute\Model;

use Magento\Framework\Model\AbstractModel;

class OptionImage extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(\Vendor\CompanyAttribute\Model\ResourceModel\OptionImage::class);
    }
}
