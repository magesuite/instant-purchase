<?php

namespace MageSuite\InstantPurchase\Observer;

class CopyInstantPurchaseOriginToOrder implements \Magento\Framework\Event\ObserverInterface
{

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $observer->getEvent()->getData('order');

        /* @var \Magento\Quote\Api\Data\CartInterface $quote */
        $quote = $observer->getEvent()->getData('quote');

        if (!$order || !$quote) {
            return;
        }

        $order->setInstantPurchaseOrigin($quote->getInstantPurchaseOrigin());
    }
}
