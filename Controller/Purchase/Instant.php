<?php

namespace MageSuite\InstantPurchase\Controller\Purchase;

class Instant extends \Magento\Framework\App\Action\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    protected \Magento\InstantPurchase\Model\QuoteManagement\Purchase $purchase;
    protected \Magento\Quote\Model\QuoteRepository $quoteRepository;
    protected \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \Psr\Log\LoggerInterface $logger;
    protected \MageSuite\InstantPurchase\Model\ResourceModel\QuoteCleaner $quoteCleaner;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\InstantPurchase\Model\QuoteManagement\Purchase $purchase,
        \MageSuite\InstantPurchase\Model\ResourceModel\QuoteCleaner $quoteCleaner,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->purchase = $purchase;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->quoteCleaner = $quoteCleaner;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $request = $this->getRequest();
        $quoteId = $request->getParam('quote_id');

        try {
            $customer = $this->customerSession->getCustomer();

            $quote = $this->quoteRepository->get($quoteId);

            if ($quote->getCustomerId() != $customer->getId()) {
                $this->logger->error(sprintf('Controller tried to buy quote from different customer'));

                throw new \Magento\Framework\Exception\LocalizedException(__('Provided quote does not exist.'));
            }

            $orderId = $this->purchase->purchase($quote);

            $this->quoteCleaner->cleanInstantPurchaseQuotes($customer->getId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->error(sprintf('Error when trying to buy using instant purchase %s', $e->getMessage()));

            return $this->createResponse($this->createGenericErrorMessage(), false);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error when trying to buy using instant purchase %s', $e->getMessage()));

            return $this->createResponse(
                $e instanceof \Magento\Framework\Exception\LocalizedException ? $e->getMessage() : $this->createGenericErrorMessage(),
                false
            );
        }

        $order = $this->orderRepository->get($orderId);
        $message = __('Your order number is: %1.', $order->getIncrementId());

        return $this->createResponse($message, true);
    }

    private function createGenericErrorMessage(): string
    {
        return (string)__('Something went wrong while processing your order. Please try again later.');
    }

    private function createResponse(string $message): \Magento\Framework\Controller\Result\Json
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $result->setData(['response' => $message]);

        return $result;
    }
}
