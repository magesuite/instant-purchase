<?php

declare(strict_types=1);

namespace MageSuite\InstantPurchase\Model;

class OrderItemToConfigurableItemWrapper implements \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface
{
    /** @var \Magento\Sales\Model\Order\Item */
    protected \Magento\Sales\Api\Data\OrderItemInterface $orderItem;

    public function __construct(
        \Magento\Sales\Api\Data\OrderItemInterface $orderItem,
    ) {
        $this->orderItem = $orderItem;
    }

    public function getProduct(): ?\Magento\Catalog\Api\Data\ProductInterface
    {
        return $this->orderItem->getProduct();
    }

    // phpcs:ignore
    public function getOptionByCode($code)
    {
        return $this->orderItem->getProductOptionByCode($code);
    }

    public function getFileDownloadParams(): ?\Magento\Framework\DataObject
    {
        return null;
    }

    public function isProductAvailable(): bool
    {
        $isAvailable = (bool) $this->orderItem->getProduct()?->isAvailable();

        if (!$isAvailable) {
            return false;
        }

        if ($this->isConfigurable()) {
            /** @var \Magento\Sales\Model\Order\Item $childItem */
            foreach ($this->getChildrenItems() as $childItem) {
                $isAvailable = (bool) $childItem->getProduct()?->isAvailable();

                if (!$isAvailable) {
                    return false;
                }
            }
        }

        return $isAvailable;
    }

    public function getChildrenItems(): array
    {
        return $this->orderItem->getChildrenItems();
    }

    public function getChildItem(): ?self
    {
        if (!$this->isConfigurable() || !$this->hasChildren()) {
            return null;
        }

        $childItem = $this->orderItem->getChildrenItems()[0];

        return new OrderItemToConfigurableItemWrapper($childItem);
    }

    protected function hasChildren(): bool
    {
        return $this->orderItem->getChildrenItems() > 0;
    }

    protected function isConfigurable(): bool
    {
        return $this->orderItem->getProductType() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
    }
}
