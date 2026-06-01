<?php

/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Klarna\Support\Test\Unit\Block\Adminhtml\Support\Button;

use Klarna\Support\Block\Adminhtml\Support\Button\Send;
use Klarna\Base\Test\Unit\Mock\TestObjectFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Klarna\Support\Block\Adminhtml\Support\Button\Send
 */
class SendTest extends TestCase
{
    /**
     * @var Send
     */
    private $send;

    /**
     * @covers ::getButtonData()
     */
    public function testGetButtonDataReturnsArray(): void
    {
        static::assertIsArray($this->send->getButtonData());
    }

    protected function setUp(): void
    {
        $objectFactory = new TestObjectFactory('');
        $this->send = $objectFactory->create(Send::class);
    }
}
