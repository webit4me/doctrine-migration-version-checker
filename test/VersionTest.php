<?php

declare(strict_types=1);

namespace Ministryofjustice\DoctrineMigrationVersionCheckerTest;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Exception;
use Ministryofjustice\DoctrineMigrationVersionChecker\Version;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    private array $mockConfig = [
        'directory' => './Fixture/DBMigrations',
        'namespace' => 'Ministryofjustice\DoctrineMigrationVersionCheckerTest\Fixture\DBMigrations',
        'table' => 'tableName',
    ];

    /**
     * @var Connection
     */
    private $mockConnection;

    public function setUp(): void
    {
        $this->mockConnection = $this->createMock(Connection::class);
    }

    public function test_getCurrentVersion_returns_latest_version(): void
    {
        chdir(__DIR__);

        $this->mockConnection->expects(self::atLeastOnce())
            ->method('getDatabasePlatform')
            ->willReturn(new SqlitePlatform());

        $this->mockConnection->expects(self::atLeastOnce())
            ->method('getSchemaManager')
            ->willReturn(new TestableSchemaManager($this->mockConnection));

        $this->mockConnection->expects(self::atLeastOnce())
            ->method('connect')
            ->willReturn(false);

        $service = new Version($this->mockConnection, $this->mockConfig);
        $result = $service->getCurrentVersion();

        self::assertEquals('', $result);
    }

    /**
     * @param array $config
     * @param Exception|null $expectedException
     *
     * @dataProvider incorrectConfigDataProvider
     */
    public function test_getCurrentVersion_throws_exceptions_when_missing_config(array $config, Exception $expectedException = null)
    {
        if (!is_null($expectedException)) {
            $this->expectException(get_class($expectedException));
            $this->expectExceptionMessage($expectedException->getMessage());
        }

        $service = new Version($this->mockConnection, $config);
        $service->getCurrentVersion();

        self::assertInstanceOf(Version::class, $service);
    }

    /**
     * @return array<mixed>
     */
    public function incorrectConfigDataProvider(): array
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
        ];
    }
}
