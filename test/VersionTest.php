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
    ];

    /**
     * @var Connection
     */
    private $mockConnection;

    /**
     * @var int
     */
    private $mockedVersionNumber = 1;

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
        $mockConnection->expects($this->any())
            ->method('fetchColumn')
            ->willReturnCallback(function () {
                return $this->mockedVersionNumber;
            });

        $this->mockConnection = $mockConnection;
    }

    public function testVersion()
    {
        $service = new Version($this->mockConnection, $this->mockConfig);

        foreach (
            [
                1 => '20171020101112',
                0 => '20171020101111',
                2 => '20171020101113',
            ] as $versionNumber => $version) {

            $this->setExpectedCurrentVersion($versionNumber);
            $this->assertEquals($version, $service->getCurrentVersion());
        }
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
                        Version::MIGRATION_DIR_NAME
                    )
                ),
            ],
            [
                'incorrectConfig' => [
                    'folder' => 'MembraneDoctrineMigrations',
                    'namespace' => 'Migrations',
                ],
                'expectedException' => new OutOfBoundsException(
                    sprintf(
                        Version::ERR_MSG_MISSING_KEY,
                        Version::MIGRATION_DIR_NAME
                    )
                ),
            ],
            [
                'incorrectConfig' => [
                    'directory' => 'MembraneDoctrineMigrations',
                    'domain' => 'Migrations',
                ],
                'expectedException' => new OutOfBoundsException(
                    sprintf(
                        Version::ERR_MSG_MISSING_KEY,
                        Version::MIGRATION_NAMESPACE_KEY
                    )
                ),
            ],
            [
                'incorrectConfig' => $this->mockConfig,
                'expectedException' => null,
            ],
        ];
    }

    /**
     * @param int $mockedVersionNumber
     */
    private function setExpectedCurrentVersion($mockedVersionNumber)
    {
        $this->mockedVersionNumber = $mockedVersionNumber;
    }
}
