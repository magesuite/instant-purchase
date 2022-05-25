<?php

namespace MageSuite\InstantPurchase\Api\Service;

interface QuoteItemsGenerationStrategyInterface
{
    public function isApplicable($params): bool;
    public function fill($params, $quote): \Magento\Quote\Model\Quote;
}
