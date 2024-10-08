<?php

namespace MageSuite\InstantPurchase\Controller\Purchase;

class QuoteDetails extends \Magento\Framework\App\Action\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    protected \Magento\InstantPurchase\Model\QuoteManagement\QuoteCreation $quoteCreation;
    protected \MageSuite\InstantPurchase\Service\QuoteItemsGenerator $quoteItemsGenerator;
    protected \Magento\InstantPurchase\Model\QuoteManagement\ShippingConfiguration $shippingConfiguration;
    protected \Magento\InstantPurchase\Model\QuoteManagement\PaymentConfiguration $paymentConfiguration;
    protected \Magento\InstantPurchase\Model\QuoteManagement\Purchase $purchase;
    protected \Magento\Framework\Controller\Result\JsonFactory $jsonFactory;
    protected \Magento\Quote\Model\Cart\ShippingMethodConverter $shippingMethodConverter;
    protected \Magento\Framework\App\Action\Context $context;
    protected \Magento\Framework\Locale\FormatInterface $localeFormat;
    protected \Magento\Catalog\Helper\Image $productImageHelper;
    protected \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalRepository;
    protected \Magento\Store\Model\StoreManagerInterface $storeManager;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \Magento\InstantPurchase\Model\InstantPurchaseOptionLoadingFactory $instantPurchaseOptionLoadingFactory;
    protected \MageSuite\InstantPurchase\Service\QuoteCreator $quoteCreator;
    protected \Magento\Quote\Api\Data\ShippingMethodInterfaceFactory $shippingMethodInterfaceFactory;
    protected \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\InstantPurchase\Model\InstantPurchaseOptionLoadingFactory $instantPurchaseOptionLoadingFactory,
        \MageSuite\InstantPurchase\Service\QuoteCreator $quoteCreator,
        \MageSuite\InstantPurchase\Service\QuoteItemsGenerator $quoteItemsGenerator,
        \Magento\InstantPurchase\Model\QuoteManagement\ShippingConfiguration $shippingConfiguration,
        \Magento\InstantPurchase\Model\QuoteManagement\PaymentConfiguration $paymentConfiguration,
        \Magento\InstantPurchase\Model\QuoteManagement\Purchase $purchase,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Quote\Model\Cart\ShippingMethodConverter $shippingMethodConverter,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Catalog\Helper\Image $productImageHelper,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalRepository,
        \Magento\Quote\Api\Data\ShippingMethodInterfaceFactory $shippingMethodInterfaceFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->instantPurchaseOptionLoadingFactory = $instantPurchaseOptionLoadingFactory;
        $this->quoteItemsGenerator = $quoteItemsGenerator;
        $this->shippingConfiguration = $shippingConfiguration;
        $this->paymentConfiguration = $paymentConfiguration;
        $this->purchase = $purchase;
        $this->jsonFactory = $jsonFactory;
        $this->shippingMethodConverter = $shippingMethodConverter;
        $this->context = $context;
        $this->localeFormat = $localeFormat;
        $this->productImageHelper = $productImageHelper;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->quoteCreator = $quoteCreator;
        $this->shippingMethodInterfaceFactory = $shippingMethodInterfaceFactory;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $request = $this->getRequest();
        $params = $this->_request->getParams();

        $paymentTokenPublicHash = (string)$request->getParam('instant_purchase_payment_token');
        $shippingAddressId = (int)$request->getParam('instant_purchase_shipping_address');
        $billingAddressId = (int)$request->getParam('instant_purchase_billing_address');
        $carrierCode = (string)$request->getParam('instant_purchase_carrier');
        $shippingMethodCode = (string)$request->getParam('instant_purchase_shipping');

        try {
            $customer = $this->customerSession->getCustomer();

            $instantPurchaseOption = $this->instantPurchaseOptionLoadingFactory->create(
                $customer->getId(),
                $paymentTokenPublicHash,
                $shippingAddressId,
                $billingAddressId,
                $carrierCode,
                $shippingMethodCode
            );

            $store = $this->storeManager->getStore();

            $quote = $this->quoteCreator->create(
                $params,
                $store,
                $customer,
                $instantPurchaseOption->getShippingAddress(),
                $instantPurchaseOption->getBillingAddress()
            );

            $quote = $this->quoteItemsGenerator->fill($params, $quote);

            if (empty($this->getQuoteItems($quote))) {
                return;
            }

            $quote->getShippingAddress()
                ->setCollectShippingRates(true);

            $quote = $this->configurePayment($quote, $instantPurchaseOption);

            if (empty($instantPurchaseOption->getShippingMethod()->getCarrierCode())) {
                $cheapestShippingMethod = $this->setCheapestShippingMethod($quote);

                $carrierCode = $cheapestShippingMethod->getCarrierCode();
                $shippingMethodCode = $cheapestShippingMethod->getMethodCode();
            } else {
                try {
                    $quote = $this->shippingConfiguration->configureShippingMethod(
                        $quote,
                        $instantPurchaseOption->getShippingMethod()
                    );
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Error when trying to set shipping method in instant purchase: %s', $e->getMessage()));

                    $cheapestShippingMethod = $this->setCheapestShippingMethod($quote);

                    $carrierCode = $cheapestShippingMethod->getCarrierCode();
                    $shippingMethodCode = $cheapestShippingMethod->getMethodCode();
                }
            }

            $quote->setTotalsCollectedFlag(false)->collectTotals()->save();

            $cartTotals = $this->cartTotalRepository->get($quote->getId());
            $items = $this->getQuoteItems($quote);
            $shippingMethods = $this->getShippingMethods($quote);
            $addresses = $this->getAddresses($customer);

            $quoteAsArray = $quote->toArray();
            $quoteAsArray['shipping_address'] = $quote->getShippingAddress()->toArray();
            $quoteAsArray['billing_address'] = $quote->getBillingAddress()->toArray();
            $quoteAsArray['shipping_carrier_code'] = $carrierCode;
            $quoteAsArray['shipping_method_code'] = $shippingMethodCode;

            $jsonResult = $this->jsonFactory->create();

            $jsonResult->setData([
                'quote' => $quoteAsArray,
                'addresses' => $addresses,
                'totals' => $cartTotals->getData(),
                'items' => $items,
                'shippingMethods' => $shippingMethods,
                'basePriceFormat' => $this->localeFormat->getPriceFormat(null, $quote->getBaseCurrencyCode())
            ]);

            return $jsonResult;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error when trying to prepare quote for instant purchase: %s', $e->getMessage()));

            $jsonResult = $this->jsonFactory->create();
            $jsonResult->setData(['success' => 'false',]);

            return $jsonResult;
        }
    }

    public function configurePayment(\Magento\Quote\Model\Quote $quote, \Magento\InstantPurchase\Model\InstantPurchaseOption $instantPurchaseOption)
    {
        return $this->paymentConfiguration->configurePayment(
            $quote,
            $instantPurchaseOption->getPaymentToken()
        );
    }

    protected function getShippingMethods($quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->collectShippingRates();

        $shippingRates = $shippingAddress->getGroupedAllShippingRates();

        $output = [];

        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $shippingMethod = $this->shippingMethodConverter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());

                $output[] = $shippingMethod->__toArray();
            }
        }

        return $output;
    }

    protected function getCheapestShippingMethod($quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->collectShippingRates();
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();

        $shippingMethods = [];

        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $shippingMethods[] = $this->shippingMethodInterfaceFactory->create()
                    ->setMethodCode($rate->getMethod())
                    ->setCarrierCode($rate->getCarrier())
                    ->setAmount($rate->getPrice());
            }
        }

        usort($shippingMethods, function ($first, $second) {
            return $first->getAmount() <=> $second->getAmount();
        });

        return array_shift($shippingMethods);
    }

    protected function setCheapestShippingMethod($quote)
    {
        $cheapestShippingMethod = $this->getCheapestShippingMethod($quote);

        $this->shippingConfiguration->configureShippingMethod(
            $quote,
            $cheapestShippingMethod
        );

        return $cheapestShippingMethod;
    }

    protected function getAddresses(\Magento\Customer\Model\Customer $customer): array
    {
        $addresses = [];

        foreach ($customer->getAddresses() as $address) {
            $addresses[] = $address->toArray();
        }

        return $addresses;
    }

    protected function getQuoteItems(\Magento\Quote\Model\Quote $quote): array
    {
        $items = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $imageUrl = $this->productImageHelper->init($item->getProduct(), 'cart_page_product_thumbnail')
                ->getUrl();

            $item->getProduct()
                ->setImageUrl($imageUrl);

            $items[] = $item->toArray();
        }

        return $items;
    }
}
