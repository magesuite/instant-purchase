<?php

declare(strict_types=1);

$resolver = \Magento\TestFramework\Workaround\Override\Fixture\Resolver::getInstance();
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$resolver->requireDataFixture('Magento/Sales/_files/default_rollback.php');
$resolver->requireDataFixture('Magento/ConfigurableProduct/_files/product_configurable.php');
$resolver->requireDataFixture('Magento/Customer/_files/customer.php');

$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$installer = $objectManager->create(\Magento\Catalog\Setup\CategorySetup::class);
$attributeSetId = $installer->getAttributeSetId(\Magento\Catalog\Model\Product::ENTITY, 'Default');
$groupId = $installer->getDefaultAttributeGroupId(\Magento\Catalog\Model\Product::ENTITY, $attributeSetId);
$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);
$optionsFactory = $objectManager->get(\Magento\ConfigurableProduct\Helper\Product\Options\Factory::class);

$secondAttributeCode = 'test_configurable_second';
$attributeFactory = $objectManager->get(\Magento\Catalog\Api\Data\ProductAttributeInterfaceFactory::class);
$attributeRepository = $objectManager->get(\Magento\Catalog\Api\ProductAttributeRepositoryInterface::class);

$secondAttributeModel = $attributeFactory->create();
$secondAttributeModel->setData([
    'attribute_code' => $secondAttributeCode,
    'entity_type_id' => $installer->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY),
    'is_global' => 1,
    'is_user_defined' => 1,
    'frontend_input' => 'select',
    'is_required' => 0,
    'frontend_label' => ['Test Configurable Second'],
    'backend_type' => 'int',
    'option' => [
        'value' => [
            'option_0' => ['Second Option 1'],
            'option_1' => ['Second Option 2'],
            'option_2' => ['Second Option 3'],
        ],
        'order' => ['option_0' => 1, 'option_1' => 2, 'option_2' => 3],
    ],
]);
$secondAttribute = $attributeRepository->save($secondAttributeModel);
$installer->addAttributeToGroup(\Magento\Catalog\Model\Product::ENTITY, $attributeSetId, $groupId, $secondAttribute->getId());
$eavConfig->clear();
$secondAttribute = $eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $secondAttributeCode);

$secondAttributeValues = [];
$secondAssociatedProductIds = [];
$secondChildIds = [30, 40, 50];
$secondOptions = $secondAttribute->getOptions();
array_shift($secondOptions);

foreach ($secondOptions as $option) {
    $childId = array_shift($secondChildIds);

    if ($childId === null) {
        break;
    }

    $childProduct = $objectManager->create(\Magento\Catalog\Model\Product::class);
    $childProduct->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
        ->setId($childId)
        ->setAttributeSetId($attributeSetId)
        ->setWebsiteIds([1])
        ->setName('Second Configurable Child ' . $childId)
        ->setSku('simple_' . $childId)
        ->setPrice($childId)
        ->setData($secondAttributeCode, $option->getValue())
        ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);
    $childProduct = $productRepository->save($childProduct);

    $secondAttributeValues[] = [
        'label' => 'test',
        'attribute_id' => $secondAttribute->getId(),
        'value_index' => $option->getValue(),
    ];
    $secondAssociatedProductIds[] = $childProduct->getId();
}

$secondConfigurableOptions = $optionsFactory->create([
    [
        'attribute_id' => $secondAttribute->getId(),
        'code' => $secondAttribute->getAttributeCode(),
        'label' => $secondAttribute->getStoreLabel(),
        'position' => '0',
        'values' => $secondAttributeValues,
    ],
]);

$secondConfigurable = $objectManager->create(\Magento\Catalog\Model\Product::class);
$secondConfigurableExtension = $secondConfigurable->getExtensionAttributes();
$secondConfigurableExtension->setConfigurableProductOptions($secondConfigurableOptions);
$secondConfigurableExtension->setConfigurableProductLinks($secondAssociatedProductIds);
$secondConfigurable->setExtensionAttributes($secondConfigurableExtension);
$secondConfigurable->setTypeId(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
    ->setId(60)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds([1])
    ->setName('Second Configurable Product')
    ->setSku('configurable_second')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);
$secondConfigurable = $productRepository->save($secondConfigurable);

$regularSimple = $objectManager->create(\Magento\Catalog\Model\Product::class);
$regularSimple->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(70)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds([1])
    ->setName('Regular Simple Product')
    ->setSku('simple_regular')
    ->setPrice(70)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);
$regularSimple = $productRepository->save($regularSimple);

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
$payment->setMethod('checkmo');

$firstConfigurable = $productRepository->getById(1);
$firstAttribute = $eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_configurable');

$firstChildrenByOptionId = [];
foreach (['simple_10', 'simple_20'] as $childSku) {
    $child = $productRepository->get($childSku);
    $firstChildrenByOptionId[(int)$child->getData('test_configurable')] = $child;
}

$secondChildrenByOptionId = [];
foreach (['simple_30', 'simple_40', 'simple_50'] as $childSku) {
    $child = $productRepository->get($childSku);
    $secondChildrenByOptionId[(int)$child->getData($secondAttributeCode)] = $child;
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

$addConfigurableGroup = function (
    \Magento\Sales\Model\Order $order,
    \Magento\Catalog\Model\Product $configurableProduct,
    int $attributeId,
    int $optionId,
    \Magento\Catalog\Model\Product $childProduct,
    int $qty
) use ($objectManager): void {
    $requestInfo = [
        'qty' => $qty,
        'super_attribute' => [$attributeId => $optionId],
    ];

    $configurableOrderItem = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
    $configurableOrderItem->setProductId($configurableProduct->getId())
        ->setQtyOrdered($qty)
        ->setBasePrice($childProduct->getPrice())
        ->setPrice($childProduct->getPrice())
        ->setRowTotal($childProduct->getPrice() * $qty)
        ->setProductType($configurableProduct->getTypeId())
        ->setName($configurableProduct->getName())
        ->setSku($childProduct->getSku())
        ->setProductOptions(['info_buyRequest' => $requestInfo]);

    $childOrderItem = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
    $childOrderItem->setProductId($childProduct->getId())
        ->setQtyOrdered($qty)
        ->setBasePrice($childProduct->getPrice())
        ->setPrice($childProduct->getPrice())
        ->setProductType($childProduct->getTypeId())
        ->setName($childProduct->getName())
        ->setSku($childProduct->getSku())
        ->setParentItem($configurableOrderItem)
        ->setProductOptions(['info_buyRequest' => $requestInfo]);

    $order->addItem($configurableOrderItem)->addItem($childOrderItem);
};

foreach ($firstChildrenByOptionId as $optionId => $childProduct) {
    $addConfigurableGroup($order, $firstConfigurable, (int)$firstAttribute->getId(), $optionId, $childProduct, 2);
}

foreach ($secondChildrenByOptionId as $optionId => $childProduct) {
    $addConfigurableGroup($order, $secondConfigurable, (int)$secondAttribute->getId(), $optionId, $childProduct, 1);
}

$simpleOrderItem = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
$simpleOrderItem->setProductId($regularSimple->getId())
    ->setQtyOrdered(1)
    ->setBasePrice($regularSimple->getPrice())
    ->setPrice($regularSimple->getPrice())
    ->setRowTotal($regularSimple->getPrice())
    ->setProductType($regularSimple->getTypeId())
    ->setName($regularSimple->getName())
    ->setSku($regularSimple->getSku())
    ->setProductOptions(['info_buyRequest' => ['qty' => 1]]);
$order->addItem($simpleOrderItem);

$orderRepository = $objectManager->create(\Magento\Sales\Api\OrderRepositoryInterface::class);
$orderRepository->save($order);
