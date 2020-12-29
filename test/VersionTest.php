<?php

declare(strict_types=1);

namespace Ministryofjustice\DoctrineMigrationVersionCheckerTest;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Exception;
use Ministryofjustice\DoctrineMigrationVersionChecker\Version;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    private array $mockConfig = [
        'table_storage' => [
            'table_name' => 'tableName',
        ],
        'migrations_paths' => [
            'Ministryofjustice\DoctrineMigrationVersionCheckerTest\Fixture\DBMigrations' => './Fixture/DBMigrations',
        ],
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

        $service = new Version($this->mockConnection, $this->mockConfig);
        $result = $service->getCurrentVersion();

        self::assertEquals('0', $result);
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
                'incorrectConfig' => [
                    'migrations_paths' => [
                        'Ministryofjustice\DoctrineMigrationVersionCheckerTest\Fixture\DBMigrations' => './Fixture/DBMigrations',
                    ],
                ],
                'expectedException' => new OutOfBoundsException(
                    sprintf(
                        Version::ERR_MSG_MISSING_KEY,
                        Version::TABLE_CONFIG_SECTION_KEY
                    )
                ),
            ],
            [
                'incorrectConfig' => [
                    'table_storage' => [
                    ],
                    'migrations_paths' => [
                        'Ministryofjustice\DoctrineMigrationVersionCheckerTest\Fixture\DBMigrations' => './Fixture/DBMigrations',
                    ],
                ],
                'expectedException' => new OutOfBoundsException(
                    sprintf(
                        Version::ERR_MSG_MISSING_KEY,
                        Version::MIGRATION_TABLE_KEY
                    )
                ),
            ],
            [
                'incorrectConfig' => [
                    'table_storage' => [
                        'table_name' => 'tableName',
                    ],
                ],
                'expectedException' => new OutOfBoundsException(
                    sprintf(
                        Version::ERR_MSG_MISSING_KEY,
                        Version::MIGRATION_PATHS_SECTION_KEY
                    )
                ),
            ],
        ];
    }
}
