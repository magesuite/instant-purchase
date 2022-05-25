<?php
declare(strict_types=1);

$resolver = \Magento\TestFramework\Workaround\Override\Fixture\Resolver::getInstance();
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$resolver->requireDataFixture('Magento/Sales/_files/default_rollback.php');
$resolver->requireDataFixture('Magento/GroupedProduct/_files/product_grouped_with_simple.php');
$resolver->requireDataFixture('Magento/Customer/_files/customer.php');

$addressData = [
    'region' => 'CA',
    'region_id' => '12',
    'postcode' => '11111',
    'lastname' => 'lastname',
    'firstname' => 'firstname',
    'street' => 'street',
    'city' => 'Los Angeles',
    'email' => 'admin@example.com',
    'telephone' => '11111111',
    'country_id' => 'US'
];


$billingAddress = $objectManager->create(\Magento\Sales\Model\Order\Address::class, ['data' => $addressData]);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');

$payment = $objectManager->create(\Magento\Sales\Model\Order\Payment::class);
$payment->setMethod('checkmo')
    ->setAdditionalInformation('last_trans_id', '11122')
    ->setAdditionalInformation(
        'metadata',
        [
            'type' => 'free',
            'fraudulent' => false,
        ]
    );

$product = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class)->get('simple_11');
$groupedProduct = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class)->get('grouped');

$productOptions = [
    'info_buyRequest' => [
        'super_product_config' => [
            'product_type' => 'grouped',
            'product_id' => $groupedProduct->getId()
        ]
    ],
    'super_product_config' => [
        'product_code' => 'product_type',
        'product_type' => 'grouped',
        'product_id' => $groupedProduct->getId()
    ]
];

$orderItem = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
$orderItem->setProductId($product->getId())
    ->setQtyOrdered(2)
    ->setBasePrice($product->getPrice())
    ->setPrice($product->getPrice())
    ->setRowTotal($product->getPrice())
    ->setProductType('grouped')
    ->setName($product->getName())
    ->setSku($product->getSku())
    ->setProductOptions($productOptions);

$order = $objectManager->create(\Magento\Sales\Model\Order::class);
$order->setIncrementId('100000002')
    ->setCustomerId(1)
    ->setCustomerIsGuest(false)
    ->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
    ->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING))
    ->setSubtotal(100)
    ->setGrandTotal(100)
    ->setBaseSubtotal(100)
    ->setBaseGrandTotal(100)
    ->setCustomerIsGuest(true)
    ->setCustomerEmail('customer@null.com')
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setStoreId($objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore()->getId())
    ->addItem($orderItem)
    ->setPayment($payment);

$orderRepository = $objectManager->create(\Magento\Sales\Api\OrderRepositoryInterface::class);
$orderRepository->save($order);
