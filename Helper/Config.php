<?php

namespace Simplypost\Shipping\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_BUNGEE_URL = 'carriers/simplypost/bungee_url';
    const XML_PATH_SIMPLYPOST_API_TOKEN = 'carriers/simplypost/token';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    )
    {
        parent::__construct($context);
    }


    public function getBungeeUrl()
    {
        return $this->_getConfigByPath(self::XML_PATH_BUNGEE_URL);
    }

    public function getSimplypostApiToken()
    {
        return $this->_getConfigByPath(self::XML_PATH_SIMPLYPOST_API_TOKEN);
    }

    private function _getConfigByPath($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
