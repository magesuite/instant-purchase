<?php

declare(strict_types=1);

namespace MageSuite\InstantPurchase\Test\Integration\Service\QuoteItemsGeneratorStrategy;

class UseOrderItemsTest extends \PHPUnit\Framework\TestCase
{
    protected \Magento\TestFramework\ObjectManager $objectManager;

    protected \Magento\Customer\Model\Session $customerSession;

    protected \Magento\Customer\Model\Customer $customer;

    protected \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory;

    protected \Magento\Quote\Model\QuoteFactory $quoteFactory;

    protected \MageSuite\InstantPurchase\Service\QuoteItemsGeneratorStrategy\UseOrderItems $useOrderItems;

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
    public function testItCopiesBoughtItemsToNewQuote(): void
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

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture MageSuite_InstantPurchase::Test/Integration/_files/order_with_customer_and_configurable_product.php
     */
    public function testItCopiesBoughtConfigurableProductToNewQuote(): void
    {
        $this->customerSession->setCustomerAsLoggedIn($this->getCustomer());

        $order = $this->orderFactory->create();
        $order->loadByIncrementId('100000002');

        $quote = $this->quoteFactory->create();

        $this->assertEmpty($quote->getAllItems());

        $params = ['qty' => [], 'reorder_item' => []];
        foreach ($this->getConfigurableOrderItems($order) as $configurableOrderItem) {
            $params['qty'][$configurableOrderItem->getId()] = 1;
            $params['reorder_item'][$configurableOrderItem->getId()] = 'on';
        }

        $this->assertCount(2, $params['reorder_item']);

        $this->useOrderItems->fill($params, $quote);

        $this->assertCount(4, $quote->getAllItems());
        $this->assertCount(2, $quote->getAllVisibleItems());

        $childSkus = [];
        foreach ($quote->getAllVisibleItems() as $parentItem) {
            $this->assertEquals(
                \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
                $parentItem->getProductType()
            );
            $this->assertEquals(1, $parentItem->getQty());
            $this->assertNotEmpty($parentItem->getBuyRequest()->getSuperAttribute());

            $children = $parentItem->getChildren();
            $this->assertCount(1, $children);

            $childItem = array_shift($children);
            $this->assertEquals(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE, $childItem->getProductType());
            $this->assertEquals(1, $childItem->getQty());

            $childSkus[] = $childItem->getSku();
        }

        sort($childSkus);
        $this->assertEquals(['simple_10', 'simple_20'], $childSkus);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture MageSuite_InstantPurchase::Test/Integration/_files/order_with_customer_and_multiple_configurable_products.php
     */
    public function testItCopiesComplexConfigurableAndSimpleCartToNewQuote(): void
    {
        $firstConfigurableId = 1;
        $secondConfigurableId = 60;

        $this->customerSession->setCustomerAsLoggedIn($this->getCustomer());

        $order = $this->orderFactory->create();
        $order->loadByIncrementId('100000002');

        $quote = $this->quoteFactory->create();

        $this->assertEmpty($quote->getAllItems());

        $params = ['qty' => [], 'reorder_item' => []];
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getParentItemId() !== null) {
                continue;
            }

            $isFirstConfigurable = (int)$orderItem->getProductId() === $firstConfigurableId
                && $orderItem->getProductType() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;

            $params['qty'][$orderItem->getId()] = $isFirstConfigurable ? 2 : 1;
            $params['reorder_item'][$orderItem->getId()] = 'on';
        }

        $this->assertCount(6, $params['reorder_item']);

        $this->useOrderItems->fill($params, $quote);

        $this->assertCount(6, $quote->getAllVisibleItems());
        $this->assertCount(11, $quote->getAllItems());

        $firstConfigurableChildSkus = [];
        $secondConfigurableChildSkus = [];
        $simpleRows = [];

        foreach ($quote->getAllVisibleItems() as $visibleItem) {
            if ($visibleItem->getProductType() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $simpleRows[] = $visibleItem;
                continue;
            }

            $children = $visibleItem->getChildren();
            $this->assertCount(1, $children);

            $childItem = array_shift($children);
            $this->assertEquals(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE, $childItem->getProductType());

            if ((int)$visibleItem->getProduct()->getId() === $firstConfigurableId) {
                $this->assertEquals(2, $visibleItem->getQty());
                $firstConfigurableChildSkus[] = $childItem->getSku();
                continue;
            }

            $this->assertEquals($secondConfigurableId, (int)$visibleItem->getProduct()->getId());
            $this->assertEquals(1, $visibleItem->getQty());
            $secondConfigurableChildSkus[] = $childItem->getSku();
        }

        sort($firstConfigurableChildSkus);
        $this->assertEquals(['simple_10', 'simple_20'], $firstConfigurableChildSkus);

        sort($secondConfigurableChildSkus);
        $this->assertEquals(['simple_30', 'simple_40', 'simple_50'], $secondConfigurableChildSkus);

        $this->assertCount(1, $simpleRows);
        $simpleRow = array_shift($simpleRows);
        $this->assertEquals('simple_regular', $simpleRow->getSku());
        $this->assertEquals(1, $simpleRow->getQty());
        $this->assertEmpty($simpleRow->getChildren());
    }

    protected function getConfigurableOrderItems(\Magento\Sales\Api\Data\OrderInterface $order): array
    {
        $configurableOrderItems = [];
        foreach ($order->getItems() as $orderItem) {
            if (
                $orderItem->getParentItemId() === null
                && $orderItem->getProductType() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
            ) {
                $configurableOrderItems[] = $orderItem;
            }
        }

        return $configurableOrderItems;
    }

    protected function getCustomer(): \Magento\Customer\Model\Customer
    {
        return $this->customer->load(1);
    }
}
