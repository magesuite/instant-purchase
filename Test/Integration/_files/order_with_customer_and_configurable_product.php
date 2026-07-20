<?php

declare(strict_types=1);

$resolver = \Magento\TestFramework\Workaround\Override\Fixture\Resolver::getInstance();
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$resolver->requireDataFixture('Magento/Sales/_files/default_rollback.php');
$resolver->requireDataFixture('Magento/ConfigurableProduct/_files/product_configurable.php');
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

$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$configurableProduct = $productRepository->getById(1);

$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);
$attribute = $eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_configurable');
$attributeId = (int)$attribute->getId();

$optionCollection = $objectManager->create(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection::class);
$options = $optionCollection->setAttributeFilter($attributeId)->getItems();

$childProductsByOptionId = [];
foreach (['simple_10', 'simple_20'] as $childSku) {
    $childProduct = $productRepository->get($childSku);
    $childProductsByOptionId[(int)$childProduct->getData('test_configurable')] = $childProduct;
}

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
    ->setCustomerEmail('customer@example.com')
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setStoreId($objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore()->getId())
    ->setPayment($payment);

foreach ($options as $option) {
    $optionId = (int)$option->getId();

    if (!isset($childProductsByOptionId[$optionId])) {
        continue;
    }

    $childProduct = $childProductsByOptionId[$optionId];

    $requestInfo = [
        'qty' => 1,
        'super_attribute' => [
            $attributeId => $optionId,
        ],
    ];

    $configurableOrderItem = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
    $configurableOrderItem->setProductId($configurableProduct->getId())
        ->setQtyOrdered(1)
        ->setBasePrice($childProduct->getPrice())
        ->setPrice($childProduct->getPrice())
        ->setRowTotal($childProduct->getPrice())
        ->setProductType($configurableProduct->getTypeId())
        ->setName($configurableProduct->getName())
        ->setSku($childProduct->getSku())
        ->setProductOptions(['info_buyRequest' => $requestInfo]);

    $childOrderItem = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
    $childOrderItem->setProductId($childProduct->getId())
        ->setQtyOrdered(1)
        ->setBasePrice($childProduct->getPrice())
        ->setPrice($childProduct->getPrice())
        ->setProductType($childProduct->getTypeId())
        ->setName($childProduct->getName())
        ->setSku($childProduct->getSku())
        ->setParentItem($configurableOrderItem)
        ->setProductOptions(['info_buyRequest' => $requestInfo]);

    $order->addItem($configurableOrderItem)->addItem($childOrderItem);
}

$orderRepository = $objectManager->create(\Magento\Sales\Api\OrderRepositoryInterface::class);
$orderRepository->save($order);
