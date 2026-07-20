<?php

declare(strict_types=1);

namespace MageSuite\InstantPurchase\Service\QuoteItemsGeneratorStrategy;

class UseOrderItems implements \MageSuite\InstantPurchase\Api\Service\QuoteItemsGenerationStrategyInterface
{
    public const USER_VISIBLE_ERROR_MESSAGES = [
        'The requested qty is not available',
        'Product that you are trying to add is not available.',
        'The required options you selected are not available.',
        'Not enough items for sale',
    ];

    protected bool $displayUserVisibleErrorMessages = true;

    // phpcs:ignore
    public function __construct(
        protected \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemsCollectionFactory,
        protected \Magento\Customer\Model\Session $customerSession,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Magento\Framework\Message\ManagerInterface $messageManager,
        protected \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
    ) {
    }

    // phpcs:ignore
    public function isApplicable($params): bool
    {
        return
            array_key_exists('reorder_item', $params) &&
            array_key_exists('qty', $params);
    }

    // phpcs:ignore
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

        if (isset($params['use_default_data'])) {
            $this->displayUserVisibleErrorMessages = (bool)$params['use_default_data'];
        }

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($orderItems as $orderItem) {
            $product = $this->getFreshProduct($orderItem, $quote);

            if ($product === null) {
                continue;
            }

            $this->addItemToCart($orderItem, $quote, $product, $itemIds[$orderItem->getId()]);
        }

        $quote->setInstantPurchaseOrigin('order_history');

        return $quote;
    }

    protected function getFreshProduct(
        \Magento\Sales\Api\Data\OrderItemInterface $orderItem,
        \Magento\Quote\Model\Quote $quote
    ): ?\Magento\Catalog\Api\Data\ProductInterface {
        try {
            return $this->productRepository->getById(
                (int)$orderItem->getProductId(),
                false,
                (int)$quote->getStoreId(),
                true
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->error(sprintf('Error when trying to load product for instant purchase reorder %s', $e->getMessage()));

            return null;
        }
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

        $product->setOrderItemId($orderItem->getItemId());

        try {
            $this->addProduct($cart, $product, $info);
            $cart->setData('item_added_to_cart_flag', true);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->error(sprintf('Error when trying to fill instant purchase quote with products %s', $e->getMessage()));

            if (!$this->displayUserVisibleErrorMessages) {
                return;
            }

            foreach (self::USER_VISIBLE_ERROR_MESSAGES as $visibleErrorMessage) {
                $errorMessage = sprintf('%s: %s', $product->getName(), $e->getMessage());

                if (__($visibleErrorMessage) == $e->getMessage()) {
                    $this->messageManager->addErrorMessage($errorMessage);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Error when trying to fill instant purchase quote with products %s', $e->getMessage()));
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addProduct(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\DataObject $info
    ): void {
        $item = $quote->addProduct($product, $info);

        if (is_string($item)) {
            throw new \Magento\Framework\Exception\LocalizedException(__($item));
        }
    }
}
