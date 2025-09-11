<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Klarna\Support\Test\Unit\Setup\Patch\Schema;

use Klarna\Support\Setup\Patch\Schema\AddSupportResourcesToKlarnaRole;
use Magento\Authorization\Model\Role;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Klarna\Base\Test\Unit\Mock\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @coversDefaultClass \Klarna\Support\Setup\Patch\Schema\AddSupportResourcesToKlarnaRole
 */
class AddSupportResourcesToKlarnaRoleTest extends TestCase
{

    /**
     * @var object
     */
    private object $addKlarnaUserAndRole;

    protected function setUp(): void
    {
        $this->addKlarnaUserAndRole = parent::setUpMocks(AddSupportResourcesToKlarnaRole::class);

        $connection = $this->createMock(AdapterInterface::class);
        $this->dependencyMocks['moduleDataSetup']
            ->method('getConnection')
            ->willReturn($connection);
    }

    public function testApplyDoesNotAddResourceToRoleIfTheRoleDoesNotExist(): void
    {
        $role = $this->createMock(Role::class);
        $role->method('getId')->willReturn(null);

        $this->dependencyMocks['klarnaUserRoleManager']
            ->expects($this->once())
            ->method('loadRoleByName')
            ->willReturn($role);

        $this->dependencyMocks['klarnaUserRoleManager']
            ->expects($this->never())
            ->method('assignResourcesToRole');

        $this->addKlarnaUserAndRole->apply();
    }

    public function testApplyAddsResourceToRoleIfTheRoleExists(): void
    {
        $this->dependencyMocks['klarnaUserRoleManager']
            ->method('checkRoleDoesNotExist')
            ->willReturn(true);

        $role = $this->createMock(Role::class);
        $role->method('getId')->willReturn('1');

        $this->dependencyMocks['klarnaUserRoleManager']
            ->expects($this->once())
            ->method('loadRoleByName')
            ->willReturn($role);

        $this->dependencyMocks['klarnaUserRoleManager']
            ->expects($this->once())
            ->method('assignResourcesToRole');

        $this->addKlarnaUserAndRole->apply();
    }

    public function testThereIsNoDependenciesForThisPatch(): void
    {
        $this->assertEquals($this->addKlarnaUserAndRole->getDependencies(), []);
    }

    public function testThereIsNoAliasesForThisPatch(): void
    {
        $this->assertEquals($this->addKlarnaUserAndRole->getAliases(), []);
    }
}
