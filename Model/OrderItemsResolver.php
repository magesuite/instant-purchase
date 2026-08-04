<?php

declare(strict_types=1);

namespace MageSuite\InstantPurchase\Model;

class OrderItemsResolver
{
    public const string ORDER_ITEMS_RESOLVED_EVENT = 'magesuite_instant_purchase_order_items_resolved';

    public function __construct(
        protected \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemsCollectionFactory,
        protected \Magento\Framework\Event\ManagerInterface $eventManager,
        protected \Magento\Framework\DataObjectFactory $dataObjectFactory
    ) {
    }

    public function getByItemIds(array $itemIds, int $customerId): array
    {
        if (empty($itemIds)) {
            return [];
        }

        /** @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection $orderItemsCollection */
        $orderItemsCollection = $this->orderItemsCollectionFactory->create();

        $orderItemsCollection->getSelect()->join(
            ['so' => $orderItemsCollection->getResource()->getTable('sales_order')],
            'main_table.order_id = so.entity_id',
            ['customer_id']
        );

        $orderItemsCollection->addFieldToFilter('item_id', ['in' => $itemIds]);
        $orderItemsCollection->addFieldToFilter('customer_id', ['eq' => $customerId]);

        $orderItems = $this->keyByItemId($orderItemsCollection->getItems());

        return $this->dispatchOrderItemsResolvedEvent($orderItems, $itemIds, $customerId);
    }

    protected function dispatchOrderItemsResolvedEvent(array $orderItems, array $itemIds, int $customerId): array
    {
        $transport = $this->dataObjectFactory->create(['data' => ['order_items' => $orderItems]]);

        $this->eventManager->dispatch(
            self::ORDER_ITEMS_RESOLVED_EVENT,
            [
                'transport' => $transport,
                'item_ids' => $itemIds,
                'customer_id' => $customerId
            ]
        );

        return (array)$transport->getData('order_items');
    }

    protected function keyByItemId(array $orderItems): array
    {
        $itemsByItemId = [];

        foreach ($orderItems as $orderItem) {
            $itemsByItemId[(int)$orderItem->getItemId()] = $orderItem;
        }

        return $itemsByItemId;
    }
}
