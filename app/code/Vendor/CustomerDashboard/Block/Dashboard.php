<?php
namespace Vendor\CustomerDashboard\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class Dashboard extends Template
{
    protected CustomerSession $customerSession;
    protected CollectionFactory $orderCollectionFactory;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CollectionFactory $orderCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    public function getCustomerId(): int
    {
        return (int) $this->customerSession->getCustomerId();
    }

    public function getCustomerName(): string
    {
        return $this->customerSession->getCustomer()->getName();
    }

    /**
     * Monthly order totals for current year (Jan–Dec)
     * Returns: ['labels' => [...], 'amounts' => [...]]
     */
    public function getMonthlyStats(): array
    {
        $customerId = $this->getCustomerId();
        $year = date('Y');

        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', ['nin' => ['canceled', 'closed']])
            ->addFieldToFilter(
                'created_at',
                ['from' => "{$year}-01-01 00:00:00", 'to' => "{$year}-12-31 23:59:59"]
            );

        $collection->getSelect()
            ->columns([
                'month' => new \Zend_Db_Expr('MONTH(created_at)'),
                'total'  => new \Zend_Db_Expr('SUM(grand_total)'),
            ])
            ->group(new \Zend_Db_Expr('MONTH(created_at)'));

        $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $amounts = array_fill(0, 12, 0);

        foreach ($collection as $row) {
            $idx = (int)$row->getData('month') - 1;
            $amounts[$idx] = round((float)$row->getData('total'), 2);
        }

        return [
            'labels'  => $monthNames,
            'amounts' => $amounts,
        ];
    }

    /**
     * Total amount this year
     */
    public function getYearTotal(): float
    {
        return array_sum($this->getMonthlyStats()['amounts']);
    }

    /**
     * Total amount this month
     */
    public function getMonthTotal(): float
    {
        $month = (int) date('n');
        return $this->getMonthlyStats()['amounts'][$month - 1];
    }
}
