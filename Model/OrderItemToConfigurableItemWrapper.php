<?php

namespace MageSuite\InstantPurchase\Model;

class OrderItemToConfigurableItemWrapper implements \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface
{
    /** @var \Magento\Sales\Model\Order\Item */
    protected $orderItem;

    public function __construct($orderItem)
    {
        $this->orderItem = $orderItem;
    }

    public function getProduct()
    {
        return $this->orderItem->getProduct();
    }

    public function getOptionByCode($code)
    {
        return $this->orderItem->getProductOptionByCode($code);
    }

    public function getFileDownloadParams()
    {
        return null;
    }
}
