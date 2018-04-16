<?php

namespace Simplypost\Shipping\Observer\Checkout;

class OnepageControllerSuccessAction implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Order Model
     *
     * @var \Magento\Sales\Model\Order $order
     */
    protected $order;

    public function __construct(
        \Simplypost\Shipping\Helper\Config $config,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Directory\Model\CountryFactory $countryFactory
    ) {
        $this->_logger = $logger;
        $this->_order = $order;
        $this->_curl = $curl;
        $this->_config = $config;
        $this->_countryFactory = $countryFactory;
    }
    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $this->_logger->debug('Simplypost start checkout');
        $orderIds = $observer->getEvent()->getData('order_ids');
        $orderId = $orderIds[0];
        $this->_logger->debug('Simplypost order id '.$orderId);
        $this->_sendOrderToSimplypost($orderId);
        return $this;
    }

    private function _sendOrderToSimplypost($orderId)
    {

        $bungee_url = $this->_config->getBungeeUrl();
        $token = $this->_config->getSimplypostApiToken();

        $this->_logger->debug('Simplypost config: '.$token);

        $order = $this->_order->load($orderId);
        $store = $order->getStore();
        $this->_logger->debug('Simplypost Store info: '.$store->getCode().' '.$store->getName());
      //get Order All Item
        $parcels = [];
        $items = $order->getItems();
        foreach ($items as $item) {
            $parcel = $item->getData();
            $this->_logger->debug('Order item: '.json_encode($parcel));
            if ($parcel['product_type'] == 'simple') {
                array_push($parcels, $parcel);
            }
        }

        $orderData = $order->getData();
        $shippingMethod = $order->getData('shipping_method');

        $this->_logger->debug('Order shipping method: '.$shippingMethod);

        if (strpos($shippingMethod, 'simplypost_') !== false) {
            $billingAddress = $order->getBillingAddress()->getData();
            $shippingAddress = $order->getShippingAddress()->getData();

            $billingAddress['country_name'] = $this->getCountryName($billingAddress['country_id']);
            $shippingAddress['country_name'] = $this->getCountryName($shippingAddress['country_id']);

            $this->_logger->debug('Order billing address: '.json_encode($billingAddress));
            $this->_logger->debug('Order shipping address: '.json_encode($shippingAddress));

            $orderData['items'] = $parcels;
            $orderData['addresses'] = ['billing' => $billingAddress, 'shipping' => $shippingAddress];

            $shipData = [
            'store' => [
            'code' => $store->getCode(),
            'name' => $store->getName()
            ],
            'order' => $orderData
            ];
            $this->_logger->debug('Simplypost order request data: '.json_encode($shipData));

            $url = $bungee_url.'api/bridges/magento/checkout';

            $this->_curl->addHeader('Simplypost-Api-Token', $token);
            $this->_curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
            $this->_curl->post($url, $shipData);

            $response = $this->_curl->getBody();
        }
    }

    public function getCountryName($countryCode)
    {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }
}
