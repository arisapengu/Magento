<?php
namespace Vendor\CustomerDashboard\Controller\Dashboard;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends Action implements HttpGetActionInterface
{
    protected PageFactory $resultPageFactory;
    protected CustomerSession $customerSession;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('My Dashboard'));
        return $resultPage;
    }
}
