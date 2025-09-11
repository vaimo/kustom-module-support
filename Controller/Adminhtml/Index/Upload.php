<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Support\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Klarna\Support\Model\Uploader;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * @internal
 */
class Upload extends Action implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var Uploader
     */
    private $uploader;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param Uploader    $uploader
     * @param Context     $context
     * @codeCoverageIgnore
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Uploader    $uploader,
        Context     $context
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->uploader          = $uploader;
    }

    /**
     * Execute upload action
     *
     * @return ResultInterface
     * @throws FileSystemException
     */
    public function execute(): ResultInterface
    {
        $result   = $this->uploader->upload();
        $response = $this->resultJsonFactory->create();
        $response->setData($result);
        return $response;
    }
}
