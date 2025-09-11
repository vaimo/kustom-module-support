<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Klarna\Support\Test\Unit\Model\Support;

use Klarna\Support\Model\Uploader;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\MediaStorage\Model\File\Uploader as FileUploader;
use Klarna\Base\Test\Unit\Mock\TestCase;

/**
 * @coversDefaultClass \Klarna\Support\Model\Uploader
 */
class UploaderTest extends TestCase
{
    /**
     * @var Uploader
     */
    private $model;
    /**
     * @var Write
     */
    private $write;
    /**
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * @covers ::upload()
     */
    public function testUploaderSaveIsCalledOnce(): void
    {
        $this->write->method('getAbsolutePath')
            ->willReturn('');
        $this->dependencyMocks['filesystem']->method('getDirectoryWrite')
            ->willReturn($this->write);
        $this->dependencyMocks['fileUploaderFactory']->method('create')
            ->willReturn($this->fileUploader);
        $this->fileUploader
            ->expects($this->once())
            ->method('save')
            ->willReturn([]);
        $this->model->upload();
    }

    /**
     * @covers ::upload()
     */
    public function testUploaderReturnsArray(): void
    {
        $this->write->method('getAbsolutePath')
            ->willReturn('');
        $this->dependencyMocks['filesystem']->method('getDirectoryWrite')
            ->willReturn($this->write);
        $this->dependencyMocks['fileUploaderFactory']->method('create')
            ->willReturn($this->fileUploader);
        $this->fileUploader
            ->expects($this->once())
            ->method('save')
            ->willReturn([]);
        self::assertIsArray($this->model->upload());
    }

    /**
     * @covers ::upload()
     */
    public function testUploaderAddsErrorMessageWhenExceptionIsThrown(): void
    {
        $this->write->method('getAbsolutePath')
            ->willReturn('');
        $this->dependencyMocks['filesystem']->method('getDirectoryWrite')
            ->willReturn($this->write);
        $this->dependencyMocks['fileUploaderFactory']->method('create')
            ->willReturn($this->fileUploader);
        $this->fileUploader
            ->expects($this->once())
            ->method('save')
            ->will($this->throwException(new FileSystemException(__('Some error has occurred.'))));
        $this->dependencyMocks['messageManager']
            ->expects($this->once())
            ->method('addErrorMessage');
        $this->model->upload();
    }

    protected function setUp(): void
    {
        $this->model = parent::setUpMocks(Uploader::class);

        $this->write           = $this->mockFactory->create(Write::class);
        $this->fileUploader    = $this->mockFactory->create(FileUploader::class);
    }
}
