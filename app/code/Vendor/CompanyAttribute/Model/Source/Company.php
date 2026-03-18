<?php
namespace Vendor\CompanyAttribute\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class Company extends AbstractSource
{
    /**
     * Return options for the "Company" select attribute.
     * Add entries here or replace with a DB/service lookup as needed.
     */
    public function getAllOptions(): array
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '', 'label' => __('-- Please Select --')],
            ];
        }

        return $this->_options;
    }
}
