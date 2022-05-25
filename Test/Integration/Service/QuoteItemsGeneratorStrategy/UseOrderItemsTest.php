<?php

namespace MageSuite\InstantPurchase\Test\Integration\Service\QuoteItemsGeneratorStrategy;

class UseOrderItemsTest extends \PHPUnit\Framework\TestCase
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
     * @var \MageSuite\InstantPurchase\Service\QuoteItemsGeneratorStrategy\UseOrderItems
     */
    protected $useOrderItems;

    public function setUp(): void
    {
        parent::setUp();
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->customerSession = $this->objectManager->get(\Magento\Customer\Model\Session::class);
        $this->customer = $this->objectManager->get(\Magento\Customer\Model\Customer::class);
        $this->orderFactory = $this->objectManager->get(\Magento\Sales\Api\Data\OrderInterfaceFactory::class);
        $this->quoteFactory = $this->objectManager->create(\Magento\Quote\Model\QuoteFactory::class);
        $this->useOrderItems = $this->objectManager->create(\MageSuite\InstantPurchase\Service\QuoteItemsGeneratorStrategy\UseOrderItems::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/order_items.php
     */
    public function testItCopiesBoughtItemsToNewQuote()
    {
        $this->customerSession->setCustomerAsLoggedIn($this->getCustomer());

        $order = $this->orderFactory->create();
        $order->loadByIncrementId('100000002');

        $quote = $this->quoteFactory->create();

        $this->assertEmpty($quote->getAllItems());

        $orderItems = $order->getItems();
        $orderItem = array_pop($orderItems);
        $orderItemId = $orderItem->getId();

        $this->useOrderItems->fill([
            'qty' => [$orderItemId => 10],
            'reorder_item' => [$orderItemId => 'on']
        ], $quote);

        $quoteItems = $quote->getAllItems();

        $this->assertNotEmpty($quoteItems);

        $quoteItem = array_pop($quoteItems);

        $this->assertEquals('Simple Product 2 sku', $quoteItem->getSku());
        $this->assertEquals(10, $quoteItem->getQty());
        $this->assertEquals(10, $quoteItem->getPrice());
    }

    protected function getCustomer()
    {
        return $this->customer->load(1);
    }
}
