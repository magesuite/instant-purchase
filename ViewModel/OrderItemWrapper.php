<?php

declare(strict_types=1);

namespace MageSuite\InstantPurchase\ViewModel;

class OrderItemWrapper implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    public function __construct(
        protected \MageSuite\InstantPurchase\Model\OrderItemToConfigurableItemWrapperFactory $wrapperFactory,
    ) {
    }

    public function create(\Magento\Sales\Api\Data\OrderItemInterface $item): \MageSuite\InstantPurchase\Model\OrderItemToConfigurableItemWrapper
    {
        return $this->wrapperFactory->create(['orderItem' => $item]);
    }
}
