<?php

namespace MageSuite\InstantPurchase\Controller\Cart;

class Add extends \Magento\Framework\App\Action\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    protected \Magento\Checkout\Model\Session $checkoutSession;
    protected \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemsCollectionFactory;
    protected \MageSuite\InstantPurchase\Service\QuoteItemsGenerator $quoteFiller;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemsCollectionFactory,
        \MageSuite\InstantPurchase\Service\QuoteItemsGenerator $quoteFiller
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->orderItemsCollectionFactory = $orderItemsCollectionFactory;
        $this->quoteFiller = $quoteFiller;
    }

    public function execute()
    {
        $postData = $this->_request->getParams();

        $quote = $this->checkoutSession->getQuote();

        $this->quoteFiller->fill($postData, $quote);

        $quote->collectTotals();
        $quote->save();

        $this->messageManager->addSuccessMessage(__('Items were successfully added to cart'));
    }
}
