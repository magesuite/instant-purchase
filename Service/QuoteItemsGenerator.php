<?php

namespace MageSuite\InstantPurchase\Service;

class QuoteItemsGenerator
{
    protected array $strategiesPool;

    /**
     * @param array $strategiesPool
     */
    public function __construct($strategiesPool = [])
    {
        $this->strategiesPool = $strategiesPool;
    }

    public function fill($params, $quote)
    {
        $strategies = $this->strategiesPool;

        foreach ($strategies as $strategy) {
            if (!$strategy->isApplicable($params)) {
                continue;
            }

            $quote = $strategy->fill($params, $quote);

            break;
        }

        return $quote;
    }
}
