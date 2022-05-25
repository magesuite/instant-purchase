<?php

namespace MageSuite\InstantPurchase\Test\Integration\Controller\Purchase;

class QuoteDetailsTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterfaceFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->customerSession = $this->objectManager->get(\Magento\Customer\Model\Session::class);
        $this->customer = $this->objectManager->get(\Magento\Customer\Model\Customer::class);
        $this->orderFactory = $this->objectManager->get(\Magento\Sales\Api\Data\OrderInterfaceFactory::class);
        $this->quoteFactory = $this->objectManager->create(\Magento\Quote\Model\QuoteFactory::class);
        $this->checkoutSession = $this->objectManager->create(\Magento\Checkout\Model\Session::class);
        $this->productRepository = $this->objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/order_items.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/InstantPurchase/_files/fake_payment_token.php
     */
    public function testItReturnsQuoteDetailsForHistoricalSales()
    {
        $customer = $this->getCustomer();
        $this->customerSession->setCustomerAsLoggedIn($customer);

        $order = $this->orderFactory->create();
        $order->loadByIncrementId('100000002');

        $orderItems = $order->getItems();
        $orderItem = array_pop($orderItems);
        $orderItemId = $orderItem->getId();

        $params = $this->getRequestParams([
            'qty' => [$orderItemId => 10],
            'reorder_item' => [$orderItemId => 'on'],
        ]);

        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setPostValue($params);

        $this->dispatch('instant_purchase/purchase/quotedetails');

        $result = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('customer@example.com', $result['quote']['customer_email']);
        $this->assertEquals(1, $result['quote']['customer_id']);

        $this->assertEquals(100, $result['totals']['subtotal']);
        $this->assertEquals(50, $result['totals']['shipping_amount']);
        $this->assertEquals(150, $result['totals']['grand_total']);

        $this->assertEquals('Simple Product 2 sku', $result['items'][0]['sku']);
        $this->assertEquals(10, $result['items'][0]['qty']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/InstantPurchase/_files/fake_payment_token.php
     */
    public function testItReturnsQuoteDetailsBasedOnCartContents()
    {
        $customer = $this->getCustomer();
        $this->customerSession->setCustomerAsLoggedIn($customer);

        $product = $this->productRepository->get('simple');

        $cartQuote = $this->checkoutSession->getQuote();
        $cartQuote->addProduct($product, new \Magento\Framework\DataObject(['qty' => 4]));
        $cartQuote->save();

        $params = $this->getRequestParams(['cart' => []]);

        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setPostValue($params);

        $this->dispatch('instant_purchase/purchase/quotedetails');

        $result = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('customer@example.com', $result['quote']['customer_email']);
        $this->assertEquals(1, $result['quote']['customer_id']);

        $this->assertEquals(40, $result['totals']['subtotal']);
        $this->assertEquals(20, $result['totals']['shipping_amount']);
        $this->assertEquals(60, $result['totals']['grand_total']);

        $this->assertEquals('simple', $result['items'][0]['sku']);
        $this->assertEquals(4, $result['items'][0]['qty']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/InstantPurchase/_files/fake_payment_token.php
     */
    public function testItReturnsQuoteDetailsProductAddedDirectly()
    {
        $customer = $this->getCustomer();
        $this->customerSession->setCustomerAsLoggedIn($customer);

        $params = $this->getRequestParams([
            'qty' => '2',
            'product' => '1',
        ]);

        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setPostValue($params);

        $this->dispatch('instant_purchase/purchase/quotedetails');

        $result = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('customer@example.com', $result['quote']['customer_email']);
        $this->assertEquals(1, $result['quote']['customer_id']);

        $this->assertEquals(20, $result['totals']['subtotal']);
        $this->assertEquals(10, $result['totals']['shipping_amount']);
        $this->assertEquals(30, $result['totals']['grand_total']);

        $this->assertEquals('simple', $result['items'][0]['sku']);
        $this->assertEquals(2, $result['items'][0]['qty']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/order_items.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/InstantPurchase/_files/fake_payment_token.php
     * @magentoDataFixture Magento/OfflineShipping/_files/tablerates.php
     * @magentoConfigFixture current_store carriers/tablerate/active 1
     * @magentoConfigFixture current_store carriers/tablerate/condition_name package_qty
     */
    public function testItSetsTheCheapestShippingMethodByDefault()
    {
        $customer = $this->getCustomer();
        $this->customerSession->setCustomerAsLoggedIn($customer);

        $order = $this->orderFactory->create();
        $order->loadByIncrementId('100000002');

        $orderItems = $order->getItems();
        $orderItem = array_pop($orderItems);
        $orderItemId = $orderItem->getId();

        $params = $this->getRequestParams([
            'qty' => [$orderItemId => 3],
            'reorder_item' => [$orderItemId => 'on'],
            'instant_purchase_carrier' => '',
            'instant_purchase_shipping' => '',
        ]);

        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setPostValue($params);

        $this->dispatch('instant_purchase/purchase/quotedetails');

        $result = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('tablerate', $result['quote']['shipping_carrier_code']);
        $this->assertEquals('bestway', $result['quote']['shipping_method_code']);
    }

    protected function getCustomer()
    {
        return $this->customer->load(1);
    }

    protected function getRequestParams($overrides)
    {
        $customer = $this->getCustomer();

        $billingAddressId = $customer->getDefaultBillingAddress()->getId();
        $shippingAddressId = $customer->getDefaultShippingAddress()->getId();

        return array_merge(
            [
                'instant_purchase_shipping_address' => $shippingAddressId,
                'instant_purchase_billing_address' => $billingAddressId,
                'instant_purchase_payment_token' => 'fakePublicHash',
                'instant_purchase_carrier' => 'flatrate',
                'instant_purchase_shipping' => 'flatrate',
            ],
            $overrides
        );
    }
}
