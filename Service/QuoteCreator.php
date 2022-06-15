<?php

namespace MageSuite\InstantPurchase\Service;

class QuoteCreator
{
    protected \Magento\Quote\Model\QuoteFactory $quoteFactory;
    protected \Magento\Checkout\Model\Session $checkoutSession;

    /**
     * @param array $strategiesPool
     */
    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
    }

    public function create( // phpcs:ignore
        $params,
        \Magento\Store\Model\Store $store,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Customer\Model\Address $shippingAddress,
        \Magento\Customer\Model\Address $billingAddress
    )
    {

        $quote = isset($params['cart']) ?
            $this->checkoutSession->getQuote() :
            $this->quoteFactory->create();

        if (isset($params['cart'])) {
            $quote->setInstantPurchaseOrigin('cart');
        }

        if ($quote->getEntityId() > 0) {
            $quote->setId($quote->getEntityId());
        }

        $quote->setStoreId($store->getId());
        $quote->setCustomer($customer->getDataModel());
        $quote->setCustomerIsGuest(0);
        $quote->getShippingAddress()
            ->importCustomerAddressData($shippingAddress->getDataModel());
        $quote->getBillingAddress()
            ->importCustomerAddressData($billingAddress->getDataModel());
        $quote->setInventoryProcessed(false);
        return $quote;
    }
}
