<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Support\Controller\Adminhtml\Index;

use Klarna\Support\Model\Email;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 * @internal
 */
class Send extends Action implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Email
     */
    private $email;
    /**
     * @var RedirectFactory
     */
    private RedirectFactory $redirectFactory;

    /**
     * @param RequestInterface $request
     * @param Email $email
     * @param RedirectFactory $redirectFactory
     * @param Context $context
     * @codeCoverageIgnore
     */
    public function __construct(
        RequestInterface $request,
        Email $email,
        RedirectFactory $redirectFactory,
        Context $context
    ) {
        parent::__construct($context);

        $this->request = $request;
        $this->email = $email;
        $this->redirectFactory = $redirectFactory;
    }

    /**
     * Execute send action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $postData = $this->request->getPostValue();
        $emailContent = $this->email->getTemplateContent($postData);

        try {
            $this->email->send($emailContent);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage("A error happened. Message: " . $e->getMessage());
            $redirect = $this->redirectFactory->create();
            $redirect->setPath('*/*/support/form/new');
            return $redirect;
        }

        $this->messageManager->addSuccessMessage(__(
            "Thank you for your support request. We will come back to you as soon as possible."
        ));

        $redirect = $this->redirectFactory->create();
        $redirect->setPath('*/*/support/form/new');
        return $redirect;
    }
}
