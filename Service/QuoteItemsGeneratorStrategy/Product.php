<?php

namespace MageSuite\InstantPurchase\Service\QuoteItemsGeneratorStrategy;

class Product implements \MageSuite\InstantPurchase\Api\Service\QuoteItemsGenerationStrategyInterface
{
    protected static $knownRequestParams = [
        'form_key',
        'product',
        'instant_purchase_payment_token',
        'instant_purchase_shipping_address',
        'instant_purchase_billing_address',
    ];

    protected \Magento\InstantPurchase\Model\QuoteManagement\QuoteFilling $quoteFilling;
    protected \Magento\Catalog\Model\ProductRepository $productRepository;

    public function __construct(
        \Magento\InstantPurchase\Model\QuoteManagement\QuoteFilling $quoteFilling,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->quoteFilling = $quoteFilling;
        $this->productRepository = $productRepository;
    }

    public function isApplicable($params): bool
    {
        return array_key_exists('product', $params);
    }

    public function fill($params, $quote): \Magento\Quote\Model\Quote
    {
        $productId = (int)$params['product'];

        $product = $this->productRepository->getById($productId);

        $productRequest = $this->getRequestUnknownParams($params);

        $this->quoteFilling->fillQuote(
            $quote,
            $product,
            $productRequest
        );

        return $quote;
    }

    private function getRequestUnknownParams($requestParams): array
    {
        $unknownParams = [];

        foreach ($requestParams as $param => $value) {
            if (!isset(self::$knownRequestParams[$param])) {
                $unknownParams[$param] = $value;
            }
        }

        return $unknownParams;
    }
}
