<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Klarna\Support\Test\Unit\Model;

use Klarna\Support\Model\Email;
use Magento\Email\Model\Transport;
use Magento\Store\Model\Store;
use Klarna\Base\Test\Unit\Mock\TestCase;
use Magento\Framework\Filesystem\Directory\Read;

/**
 * @coversDefaultClass \Klarna\Support\Model\Email
 */
class EmailTest extends TestCase
{
    /**
     * @var Email
     */
    private $email;
    /**
     * @var Read
     */
    private $read;
    /**
     * @var string[]
     */
    private $contentData;

    /**
     * @covers ::getTemplateContent()
     */
    public function testGetTemplateContentReturnsArray(): void
    {
        static::assertIsArray($this->email->getTemplateContent($this->contentData));
    }

    /**
     * @covers ::getTemplateContent()
     */
    public function testGetTemplateContentModuleListJustHasKlarna(): void
    {
        $result = $this->email->getTemplateContent($this->contentData);
        static::assertFalse(strpos($result['module_versions'], 'Any_random_module'));
    }

    /**
     * @covers ::getTemplateContent()
     */
    public function testGetTemplateContentReturnsNotEmptyPhpVersion(): void
    {
        $result = $this->email->getTemplateContent($this->contentData);
        static::assertNotEmpty($result['php_version']);
    }

    /**
     * @covers ::getTemplateContent()
     */
    public function testGetTemplateContentReturnsCorrectShopInformation(): void
    {
        $result = $this->email->getTemplateContent($this->contentData);
        static::assertSame('2.4.3 Community', $result['shop_version']);
    }

    /**
     * @covers ::getTemplateContent()
     */
    public function testGetTemplateContentRemovedCodeInInput(): void
    {
        $this->contentData['contact_name'] = "<script>my script.</script><?php my php code ?>";
        $this->dependencyMocks['escaper']->method('escapeHtml')
            ->willReturn('my script.');

        $result = $this->email->getTemplateContent($this->contentData);
        static::assertSame('my script.', $result['contact_name']);
    }

    /**
     * @covers ::send()
     * @doesNotPerformAssertions
     */
    public function testSendUsingTheCorrectTemplate(): void
    {
        $this->setUpSendTest();

        $this->dependencyMocks['transportBuilder']->method('setTemplateIdentifier')
            ->with(Email::TEMPLATE_NAME);

        $this->email->send($this->contentData);
    }

    /**
     * @covers ::send()
     */
    public function testSendWithoutAttachments(): void
    {
        $this->setUpSendTest();

        $this->dependencyMocks['transportBuilder']
            ->expects($this->never())
            ->method('addAttachment');

        $this->email->send($this->contentData);
    }

    /**
     * @covers ::send()
     */
    public function testSendWithOneAttachment(): void
    {
        $this->setUpSendTest();

        $this->contentData['attachment'] = [
            [
                'path' => '/path',
                'file' => 'file_1.jpg',
                'name' => 'file (1).jpg',
            ]
        ];

        $this->read->expects(static::once())
            ->method('getAbsolutePath')
            ->willReturn('/var/klarna');

        $this->dependencyMocks['driverInterface']
            ->expects($this->once())
            ->method('fileGetContents')
            ->with('/var/klarna/file_1.jpg')
            ->willReturn('content1');

        $this->dependencyMocks['transportBuilder']
            ->expects($this->once())
            ->method('addAttachment')
            ->with('content1', 'file (1).jpg');

        $this->dependencyMocks['file']
            ->method('getPathInfo')
            ->willReturn(['basename' => 'file_1.jpg']);

        $this->email->send($this->contentData);
    }

    /**
     * @dataProvider modulesToIgnoreDataProvider
     * @covers ::getModulesToIgnore()
     */
    public function testGetModulesToIgnore($formData, $expectedModulesToIgnore): void
    {
        $modulesToIgnore = $this->email->getModulesToIgnore($formData);

        $this->assertEquals($expectedModulesToIgnore, $modulesToIgnore);
    }

    public function modulesToIgnoreDataProvider(): array
    {
        return [
            'ignore_klarna_kp' => [
                'formData' => [
                    'data' => [
                        'OM',
                        'OSM',
                        'GraphQL',
                        'KCO',
                        'KSA',
                        'SIWK',
                        'KEC'
                    ],
                    'include_klarna_settings' => "1",
                    'include_tax_settings' => "1",
                ],
                'expectedModulesToIgnore' => ['klarna_kp']
            ],
            'jibirish_module_can_not_be_ignored' => [
                'formData' => [
                    'data' => [
                        'JiBIrish',
                        'KP',
                        'OM',
                        'OSM',
                        'GraphQL',
                        'KCO',
                        'KSA',
                        'SIWK',
                        'KEC'
                    ],
                    'include_klarna_settings' => "1",
                    'include_tax_settings' => "1",
                ],
                'expectedModulesToIgnore' => []
            ],
            'ignore_klarna_configs' => [
                'formData' => [
                    'data' => [
                        'KP',
                        'OM',
                        'OSM',
                        'GraphQL',
                        'KCO',
                        'KSA',
                        'SIWK',
                        'KEC'
                    ],
                    'include_klarna_settings' => "0",
                    'include_tax_settings' => "1",
                ],
                'expectedModulesToIgnore' => ['klarna_configs']
            ],
            'ignore_klarna_tax_configs' => [
                'formData' => [
                    'data' => [
                        'KP',
                        'OM',
                        'OSM',
                        'GraphQL',
                        'KCO',
                        'KSA',
                        'SIWK',
                        'KEC'
                    ],
                    'include_klarna_settings' => "1",
                    'include_tax_settings' => "0",
                ],
                'expectedModulesToIgnore' => ['klarna_tax_configs']
            ],
            'ignore_klarna_configs_and_tax_configs' => [
                'formData' => [
                    'data' => [
                        'KP',
                        'OM',
                        'OSM',
                        'GraphQL',
                        'KCO',
                        'KSA',
                        'SIWK',
                        'KEC'
                    ],
                    'include_klarna_settings' => "0",
                    'include_tax_settings' => '0',
                ],
                'expectedModulesToIgnore' => ['klarna_configs', 'klarna_tax_configs']
            ],
        ];
    }

    public function testGetDebugDataToSeeIfItCollectDataFromKlarnaDebugDataCollector(): void
    {
        $this->dependencyMocks['eventManager']
            ->expects($this->once())
            ->method('dispatch')
            ->with('klarna_debug_data_collector');

        $this->dependencyMocks['dataObject']
            ->expects($this->once())
            ->method('getData');

        $formData = [
            'data' => [
                'KP',
                'OM',
                'OSM',
                'GraphQL',
                'KCO',
                'KSA',
                'SIWK',
                'KEC'
            ],
            'include_klarna_settings' => "0",
            'include_tax_settings' => '0',
        ];

        $this->email->getDebugData($formData);
    }

    private function setUpSendTest()
    {
        $this->contentData['contact_name'] = 'abc';
        $this->contentData['contact_email'] = 'def';

        $store = $this->mockFactory->create(Store::class);
        $this->dependencyMocks['storeManager']->method('getStore')
            ->willReturn($store);

        $transport = $this->mockFactory->create(Transport::class);
        $this->dependencyMocks['transportBuilder']->method('getTransport')
            ->willReturn($transport);
    }

    protected function setUp(): void
    {
        $this->email = parent::setUpMocks(Email::class);
        $this->read = $this->mockFactory->create(Read::class);

        $this->dependencyMocks['moduleList']->method('getAll')
            ->willReturn(
                [
                    'Any_random_module' => ['setup_version' => '4.5.6'],
                    'Klarna_Support' => ['setup_version' => '7.8.9']
                ]
            );
        $this->dependencyMocks['productMetadata']->method('getVersion')
            ->willReturn('2.4.3');
        $this->dependencyMocks['productMetadata']->method('getEdition')
            ->willReturn('Community');
        $this->dependencyMocks['filesystem']->method('getDirectoryRead')
            ->willReturn($this->read);

        $this->contentData = [
            'data' => [
                'KP',
                'Base'
            ]
        ];
    }
}
