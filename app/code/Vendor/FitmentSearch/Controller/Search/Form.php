<?php
declare(strict_types=1);
namespace Vendor\FitmentSearch\Controller\Search;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Form implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory
    ) {}

    public function execute()
    {
        return $this->pageFactory->create();
    }
}
