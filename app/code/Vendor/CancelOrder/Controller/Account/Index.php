<?php
declare(strict_types=1);

namespace Vendor\CancelOrder\Controller\Account;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory       $pageFactory,
        private readonly CustomerSession   $customerSession,
        private readonly RedirectFactory   $redirectFactory
    ) {}

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('customer/account/login');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('My Cancellations'));
        return $page;
    }
}
