<?php
declare(strict_types=1);

namespace Vendor\FitmentSearch\Plugin;

use Magento\Framework\View\Element\BlockFactory;
use Magento\LayeredNavigation\Block\Navigation;
use Vendor\FitmentSearch\Block\Search\Form;

class AppendWidgetToNavigation
{
    public function __construct(
        private readonly BlockFactory $blockFactory
    ) {}

    public function afterToHtml(Navigation $subject, string $result): string
    {
        if (!$result) {
            return $result;
        }

        /** @var Form $block */
        $block = $this->blockFactory->createBlock(
            Form::class,
            ['data' => ['template' => 'Vendor_FitmentSearch::search/widget.phtml']]
        );

        return $result . $block->toHtml();
    }
}
