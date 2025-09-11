<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Klarna\Support\Test\Unit\Model\System\Config\Source;

use Klarna\Support\Model\System\Config\Source\Products;
use Klarna\Base\Test\Unit\Mock\TestCase;

/**
 * @coversDefaultClass \Klarna\Support\Model\System\Config\Source\Products
 */
class ProductsTest extends TestCase
{
    /**
     * @var Products
     */
    private $model;

    /**
     * @covers ::toOptionArray()
     */
    public function testToOptionArrayReturnsArray(): void
    {
        static::assertIsArray($this->model->toOptionArray());
    }

    /**
     * @covers ::toOptionArray()
     */
    public function testToOptionArrayReturnsNotEmptyResult(): void
    {
        static::assertNotEmpty($this->model->toOptionArray());
    }

    /**
     * @covers ::toOptionArray()
     */
    public function testToOptionArrayReturnsRequiredArrayKeys(): void
    {
        $optionArray = $this->model->toOptionArray();
        static::assertArrayHasKey('value', $optionArray[0]);
        static::assertArrayHasKey('label', $optionArray[0]);
        static::assertArrayHasKey('__disableTmpl', $optionArray[0]);
    }

    protected function setUp(): void
    {
        $this->model = parent::setUpMocks(Products::class);
    }
}
