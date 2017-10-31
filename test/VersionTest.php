<?php

namespace Webit4me\DoctrineMigrationVersionCheckerTest;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Webit4me\DoctrineMigrationVersionChecker\Exception\OutOfBoundsException;
use Webit4me\DoctrineMigrationVersionChecker\Version;

class VersionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $mockConfig = [
        'directory' => './Fixture/DBMigrations',
        'namespace' => 'Webit4me\DoctrineMigrationVersionCheckerTest\Fixture\DBMigrations',
        'table' => 'tableName',
    ];

    /**
     * @var Connection
     */
    private $mockConnection;

    public function setUp()
    {
        chdir(__DIR__);

        $mockConnection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();

        $mockAbstractSchemaManager = $this->getMockForAbstractClass(
            AbstractSchemaManager::class,
            [$mockConnection],
            '',
            true,
            true,
            true,
            ['listTableNames', 'createTable']
        );

        $mockAbstractSchemaManager->expects($this->any())->method('listTableNames')->willReturn([]);

        $mockConnection->expects($this->any())
            ->method('getSchemaManager')
            ->willReturn($mockAbstractSchemaManager);
        $mockConnection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new SqlitePlatform());

        $this->mockConnection = $mockConnection;
    }

    /**
     * @param array $config
     * @param \Exception|null $expectedException
     *
     * @dataProvider incorrectConfigDataProvider
     */
    public function testExceptionOnMissingConfigItems(array $config, \Exception $expectedException = null)
    {
        if (!is_null($expectedException)) {

            $this->setExpectedException(
                get_class($expectedException),
                $expectedException->getMessage(),
                $expectedException->getCode()
            );
        }

        $service = new Version($this->mockConnection, $config);
        $service->getCurrentVersion();

        $this->assertInstanceOf(Version::class, $service);
    }

    /**
     * @return array
     */
    public function incorrectConfigDataProvider()
    {
        return [
            [
                'incorrectConfig' => [],
                'expectedException' => new OutOfBoundsException(
                    sprintf(
                        Version::ERR_MSG_MISSING_KEY,
                        Version::MIGRATION_DIR_KEY
                    )
                ),
            ],
            [
                'incorrectConfig' => [
                    'folder' => 'MembraneDoctrineMigrations',
                    'namespace' => 'Migrations',
                    'table' => 'TableName',
                ],
                'expectedException' => new OutOfBoundsException(
                    sprintf(
                        Version::ERR_MSG_MISSING_KEY,
                        Version::MIGRATION_DIR_KEY
                    )
                ),
            ],
            [
                'incorrectConfig' => [
                    'directory' => 'MembraneDoctrineMigrations',
                    'domain' => 'Migrations',
                    'table' => 'TableName',
                ],
                'expectedException' => new OutOfBoundsException(
                    sprintf(
                        Version::ERR_MSG_MISSING_KEY,
                        Version::MIGRATION_NAMESPACE_KEY
                    )
                ),
            ],
            [
                'incorrectConfig' => [
                    'directory' => 'MembraneDoctrineMigrations',
                    'namespace' => 'Migrations',
                    'storage' => 'TableName',
                ],
                'expectedException' => new OutOfBoundsException(
                    sprintf(
                        Version::ERR_MSG_MISSING_KEY,
                        Version::MIGRATION_TABLE_KEY
                    )
                ),
            ],
            [
                'incorrectConfig' => $this->mockConfig,
                'expectedException' => null,
            ],
        ];
    }
}
