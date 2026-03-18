<?php
declare(strict_types=1);

namespace Vendor\WelcomePopup\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Popup extends Template
{
    private const XML_PATH_ENABLED       = 'welcome_popup/general/enabled';
    private const XML_PATH_TITLE         = 'welcome_popup/general/title';
    private const XML_PATH_CONTENT       = 'welcome_popup/general/content';
    private const XML_PATH_BUTTON_TEXT   = 'welcome_popup/general/button_text';
    private const XML_PATH_COOKIE_DAYS   = 'welcome_popup/general/cookie_lifetime';

    public function __construct(
        Template\Context $context,
        private readonly ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getPopupTitle(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getPopupContent(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CONTENT,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getButtonText(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_BUTTON_TEXT,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getCookieLifetimeDays(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_COOKIE_DAYS,
            ScopeInterface::SCOPE_STORE
        );
    }
}
