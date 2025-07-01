<?php

declare(strict_types=1);

namespace MageSuite\InstantPurchase\Controller\Cart;

class Add extends \Magento\Framework\App\Action\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        protected \Magento\Checkout\Model\Session $checkoutSession,
        protected \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemsCollectionFactory,
        protected \MageSuite\InstantPurchase\Service\QuoteItemsGenerator $quoteFiller,
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $status = false;

        try {
            $postData = $this->_request->getParams();
            $quote = $this->checkoutSession->getQuote();
            $this->quoteFiller->fill($postData, $quote);

            $quote->collectTotals();
            $quote->save();
            $status = (bool) $quote->getData('item_added_to_cart_flag');

            if ($status) {
                $this->messageManager->addSuccessMessage(__('Items were successfully added to cart'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $result->setData([
            'status' => $status,
        ]);

        return $result;
    }
}
