<?php

namespace MageSuite\InstantPurchase\Model\ResourceModel;

class QuoteCleaner
{
    protected \Magento\Framework\DB\Adapter\AdapterInterface $connection;

    public function __construct(\Magento\Framework\App\ResourceConnection $resourceConnection)
    {
        $this->connection = $resourceConnection->getConnection();
    }

    public function cleanLeftoverInstantPurchaseQuotes($customerId)
    {
        if(!is_numeric($customerId)) {
            return;
        }

        if($customerId <= 0) {
            return;
        }

        $this->connection->delete(
            $this->connection->getTableName('quote'),
            [
                'customer_id = ?' => $customerId,
                'instant_purchase_origin != ""',
                'instant_purchase_origin != ?' => 'cart'
            ]
        );

        $this->connection->delete(
            $this->connection->getTableName('quote'),
            [
                'customer_id = ?' => $customerId,
                'instant_purchase_origin = "cart"',
                'is_active = ?' => 0
            ]
        );
    }
}
