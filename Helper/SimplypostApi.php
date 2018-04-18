<?php

namespace Simplypost\Shipping\Helper;

class SimplypostApi extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->_curl = $curl;
        parent::__construct($context);
    }
}
