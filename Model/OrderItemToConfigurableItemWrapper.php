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

        if ($this->orderItem->getProductType() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            /** @var \Magento\Sales\Model\Order\Item $childItem */
            foreach ($this->orderItem->getChildrenItems() as $childItem) {
                $isAvailable = (bool) $childItem->getProduct()?->isAvailable();

                if (!$isAvailable) {
                    return false;
                }
            }
        }

        return $isAvailable;
    }
}
