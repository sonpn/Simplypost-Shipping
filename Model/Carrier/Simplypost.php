<?php


namespace Simplypost\Shipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

class Simplypost extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{

    protected $_code = 'simplypost';

    protected $_isFixed = true;

    protected $_rateResultFactory;

    protected $_rateMethodFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Simplypost\Shipping\Helper\Config $config,
        array $data = []
    ) {
        $this->_logger = $logger;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_curl = $curl;
        $this->_config = $config;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $price = $cart->getQuote()->getGrandTotal();

        $weight = $request->getPackageWeight();
        $countryCode = $request->getDestCountryId();

        $bungee_url = $this->_config->getBungeeUrl();
        $this->_logger->debug("Simplypost API url".$bungee_url);

        $token = $this->_config->getSimplypostApiToken();
        $this->_logger->debug("Simplypost token".$token);

        if (!isset($token) || trim($token)==='') {
            return false;
        }

        $this->_logger->debug("Simplypost token ".$token);

        $url = $bungee_url.'api/gateway/v1/services?weight='.strval($weight).'&price='.strval($price).'&countryCode='.$countryCode;

        $this->_logger->debug("Simplypost request url ".$url);

        $this->_curl->addHeader('Content-Type', 'application/json');
        $this->_curl->addHeader('Simplypost-Api-Token', $token);
        $this->_curl->get($url);

        $response = $this->_curl->getBody();

        $this->_logger->debug("Simplypost service type data ".$response);

        $shippingMethods = json_decode($response);

        $result = $this->_rateResultFactory->create();

        foreach ($shippingMethods as $shippingMethod) {
            $this->_logger->debug("Simplypost service type ".$shippingMethod->code);
            $method = $this->_rateMethodFactory->create();
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod($shippingMethod->code);
            $method->setMethodTitle($shippingMethod->name);

            $method->setPrice($shippingMethod->price);
            $method->setCost($shippingMethod->price);

            $result->append($method);
        }

        return $result;
    }

    /**
     * getAllowedMethods
     *
     * @param array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}
