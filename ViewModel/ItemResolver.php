<?php

declare(strict_types=1);

namespace MageSuite\InstantPurchase\ViewModel;

class ItemResolver implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    public function __construct(
        protected \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver,
    ) {
    }

    public function getFinalProduct(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item): \Magento\Catalog\Api\Data\ProductInterface
    {
        return $this->itemResolver->getFinalProduct($item);
    }
}
