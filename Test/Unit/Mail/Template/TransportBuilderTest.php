<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Klarna\Support\Test\Unit\Mail\Template;

use Klarna\Support\Mail\Template\TransportBuilder;
use Magento\Email\Model\Template;
use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Mail\EmailMessage;
use Magento\Framework\Mail\MimeMessage;
use Magento\Framework\Mail\MimePart;
use Magento\Framework\Mail\MimePartInterface;
use Magento\Framework\Mail\Transport;
use PHPUnit\Framework\MockObject\MockObject;
use Klarna\Base\Test\Unit\Mock\TestCase;
use Symfony\Component\Mime\Part\TextPart;

/**
 * @coversDefaultClass \Klarna\Support\Mail\Template\TransportBuilder
 */
class TransportBuilderTest extends TestCase
{
    /**
     * @var TransportBuilder
     */
    private $model;

    /**
     * @covers ::getTransport()
     */
    public function testGetTransportWithNoAttachments(): void
    {
        $this->setUpGetTransportTest();

        $result = $this->model->getTransport();

        $parts = $result->getMessage()->getBody()->getParts();

        self::assertCount(1, $parts);
        self::assertSame('BODY_TEXT', $parts[0]->getContent());
    }

    /**
     * @covers ::getTransport()
     */
    public function testGetTransportWithTwoDifferentAttachments(): void
    {
        $this->setUpGetTransportTest();

        $this->model->addAttachment('attachmentContent1', 'fileName1');
        $this->model->addAttachment('attachmentContent2', 'fileName2');
        $result = $this->model->getTransport();

        $parts = $result->getMessage()->getBody()->getParts();

        self::assertCount(3, $parts);
        self::assertSame('BODY_TEXT', $parts[0]->getContent());
        self::assertSame('attachmentContent1', $parts[1]->getContent());
        self::assertSame('attachmentContent2', $parts[2]->getContent());
    }

    /**
     * @covers ::addAttachment()
     */
    public function testAddAttachment(): void
    {
        $this->dependencyMocks['mimePartInterfaceFactory']
            ->expects($this->once())
            ->method('create')
            ->with([
                'content' => 'content1',
                'fileName' => 'fileName1',
                'disposition' => 'attachment',
                'encoding' => 'base64',
                'type' => 'application/octet-stream',
            ]);

        $this->model->addAttachment('content1', 'fileName1');
    }

    /**
     * Set up of the getTransport() tests
     */
    private function setUpGetTransportTest()
    {
        $templateNamespace = 'TEMPLATE_MODEL';
        $templateType = TemplateTypesInterface::TYPE_HTML;
        $bodyText = 'BODY_TEXT';
        $vars = [];
        $options = [];

        $this->model->setTemplateModel($templateNamespace);

        $body = $this->mockFactory->create(TextPart::class, [], ['getParts']);
        $this->dependencyMocks['mimeMessageInterfaceFactory']->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($input) use ($body) {
                $body
                    ->method('getParts')
                    ->willReturn($input['parts']);
            });

        /** @var EmailMessageInterface|MockObject $emailMessage */
        $emailMessage = $this->mockFactory->create(EmailMessage::class);
        $this->dependencyMocks['emailMessageInterfaceFactory']->expects($this->any())
            ->method('create')
            ->willReturnCallback(function () use ($emailMessage, $body) {
                $emailMessage
                    ->method('getBody')
                    ->willReturn($body);
                return $emailMessage;
            });

        $transport = $this->mockFactory->create(Transport::class);
        $this->dependencyMocks['mailTransportFactory']->expects($this->atLeastOnce())
            ->method('create')
            ->willReturnCallback(function ($array) use ($transport) {
                $transport
                    ->method('getMessage')
                    ->willReturn($array['message']);
                return $transport;
            });

        $this->dependencyMocks['mimePartInterfaceFactory']
            ->method('create')
            ->willReturnCallback(function ($input) {
                /** @var MimePartInterface|MockObject $mimePartMock */
                $mimePartMock = $this->mockFactory->create(MimePart::class);

                $mimePartMock
                    ->method('getContent')
                    ->willReturn($input['content']);
                return $mimePartMock;
            });

        $template = $this->mockFactory->create(Template::class);
        $template->expects($this->once())->method('setVars')->with($vars)->willReturnSelf();
        $template->expects($this->once())->method('setOptions')->with($options)->willReturnSelf();
        $template->expects($this->once())->method('getSubject')->willReturn('Email Subject');
        $template->expects($this->once())->method('getType')->willReturn($templateType);
        $template->expects($this->once())->method('processTemplate')->willReturn($bodyText);

        $this->dependencyMocks['templateFactory']->expects($this->once())
            ->method('get')
            ->with('identifier', $templateNamespace)
            ->willReturn($template);

        $this->model->setTemplateIdentifier('identifier')->setTemplateVars($vars)->setTemplateOptions($options);
    }

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->model = parent::setUpMocks(TransportBuilder::class);
    }
}
