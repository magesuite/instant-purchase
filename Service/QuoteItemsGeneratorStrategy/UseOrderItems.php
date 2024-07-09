<?php

namespace MageSuite\InstantPurchase\Service\QuoteItemsGeneratorStrategy;

class UseOrderItems implements \MageSuite\InstantPurchase\Api\Service\QuoteItemsGenerationStrategyInterface
{
    const USER_VISIBLE_ERROR_MESSAGES = [
        'The requested qty is not available',
        'Product that you are trying to add is not available.'
    ];

    protected \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemsCollectionFactory;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \Psr\Log\LoggerInterface $logger;
    protected \Magento\Framework\Message\ManagerInterface $messageManager;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemsCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->orderItemsCollectionFactory = $orderItemsCollectionFactory;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
    }

    public function isApplicable($params): bool
    {
        return
            array_key_exists('reorder_item', $params) &&
            array_key_exists('qty', $params);
    }

    public function fill($params, $quote): \Magento\Quote\Model\Quote
    {
        $itemIds = [];

        foreach ($params['reorder_item'] as $itemId => $value) {
            $itemIds[$itemId] = $params['qty'][$itemId];
        }

        if (empty($itemIds)) {
            return $quote;
        }

        /** @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection $orderItemsCollection */
        $orderItemsCollection = $this->orderItemsCollectionFactory->create();

        $orderItemsCollection->getSelect()->join(
            ['so' => $orderItemsCollection->getResource()->getTable('sales_order')],
            'main_table.order_id = so.entity_id',
            ['customer_id']
        );

        $orderItemsCollection->addFieldToFilter('item_id', ['in' => array_keys($itemIds)]);
        $orderItemsCollection->addFieldToFilter('customer_id', ['eq' => $this->customerSession->getCustomerId()]);

        $orderItems = $orderItemsCollection->getItems();

        if (empty($orderItems)) {
            return $quote;
        }

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($orderItems as $orderItem) {
            $this->addItemToCart($orderItem, $quote, $orderItem->getProduct(), $itemIds[$orderItem->getId()]);
        }

        $quote->setInstantPurchaseOrigin('order_history');

        return $quote;
    }

    protected function addItemToCart( // phpcs:ignore
        \Magento\Sales\Api\Data\OrderItemInterface $orderItem,
        \Magento\Quote\Model\Quote $cart,
        \Magento\Catalog\Model\Product $product,
        $qty = null
    ): void {
        $info = $orderItem->getProductOptionByCode('info_buyRequest');

        if (array_key_exists('custom_price', $info)) {
            unset($info['custom_price']);
        }

        $info = new \Magento\Framework\DataObject($info);
        $info->setQty($qty ?? $orderItem->getQtyOrdered());

        try {
            $cart->addProduct($product, $info);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            foreach (self::USER_VISIBLE_ERROR_MESSAGES as $visibleErrorMessage) {
                if (__($visibleErrorMessage) == $e->getMessage()) {
                    $this->messageManager->addErrorMessage(sprintf('%s: %s', $product->getName(), $e->getMessage()));
                }
            }

            $this->logger->error(sprintf('Error when trying to fill instant purchase quote with products %s', $e->getMessage()));
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Error when trying to fill instant purchase quote with products %s', $e->getMessage()));
        }
    }
}
