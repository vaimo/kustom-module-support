<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Klarna\Support\Test\Unit\Ui\DataProvider;

use Magento\Backend\Model\Auth;
use Magento\User\Model\User;
use Klarna\Support\Ui\DataProvider\Support;
use Klarna\Base\Test\Unit\Mock\MockFactory;
use Klarna\Base\Test\Unit\Mock\TestObjectFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Klarna\Support\Ui\DataProvider\Support
 */
class SupportTest extends TestCase
{
    /**
     * @var User
     */
    private $user;
    /**
     * @var Auth
     */
    private $auth;
    /**
     * @var Support
     */
    private $dataProvider;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject[]
     */
    private $dependencyMocks;
    /**
     * @var MockFactory
     */
    private $mockFactory;

    /**
     * @covers ::getData()
     */
    public function testGetDataReturnsAdminUserData(): void
    {
        $expected = [
            'new' => [
                'contact_name' => 'admin',
                'contact_email'   => 'test@klarna.de'
            ]
        ];

        $this->user->method('getName')
            ->willReturn('admin');
        $this->user->method('getEmail')
            ->willReturn('test@klarna.de');
        $this->auth->method('getUser')
            ->willReturn($this->user);

        $actual = $this->dataProvider->getData();

        static::assertSame($actual, $expected);
    }

    private function createSubject()
    {
        return new Support(
            "",
            "",
            "",
            $this->auth
        );
    }

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->mockFactory     = new MockFactory($this);
        $objectFactory         = new TestObjectFactory($this->mockFactory);
        $this->user            = $this->mockFactory->create(User::class);
        $this->auth            = $this->mockFactory->create(Auth::class);
        $this->dataProvider    = $this->createSubject();
        $this->dependencyMocks = $objectFactory->getDependencyMocks();
    }
}
