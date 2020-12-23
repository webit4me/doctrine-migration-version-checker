<?php

declare(strict_types=1);

namespace Ministryofjustice\DoctrineMigrationVersionChecker;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use InvalidArgumentException;
use OutOfBoundsException;

class Version
{
    /**
     * @see defaault keys undert migrations_configuration, orm_default at
     *   https://github.com/doctrine/DoctrineORMModule/blob/master/config/module.config.php#L174
     */
    const MIGRATION_DIR_KEY = 'directory';
    const MIGRATION_NAMESPACE_KEY = 'namespace';
    const MIGRATION_TABLE_KEY = 'table';

    const ERR_MSG_MISSING_KEY = 'Expected key "%s" is missing in the doctrine\'s migration configuration!';

    private Connection $conn;

    /**
     * @var array<mixed>
     */
    private array $doctrineMigrationConfig;

    private ?Configuration $migrationConfiguration;

    /**
     * DBMigrationVersionService constructor.
     * @param Connection $connection
     * @param array<mixed> $doctrineMigrationConfig
     *   in a usual setup comes from 'doctrine' => [ 'migrations_configuration' => ['orm_default' => []]
     * @throws InvalidArgumentException
     */
    public function __construct(Connection $connection, array $doctrineMigrationConfig)
    {
        $this->conn = $connection;
        $this->doctrineMigrationConfig = $doctrineMigrationConfig;
        $this->migrationConfiguration = null;
    }

    public function getCurrentVersion(): string
    {
        return $this->getMigrationConfiguration()->getCurrentVersion();
    }

    private function getMigrationConfiguration(): Configuration
    {
        if (is_null($this->migrationConfiguration)) {
            $this->initMigrationConfiguration();
        }

        return $this->migrationConfiguration;
    }

    private function initMigrationConfiguration(): void
    {
        $dirName = $this->getMigrationDirectory();
        $migrationTable = $this->getMigrationTable();
        $migrationNamespace = $this->getMigrationNamespace();

        $configuration = new Configuration($this->conn);
        $configuration->setMigrationsTableName($migrationTable);
        $configuration->setMigrationsNamespace($migrationNamespace);
        $configuration->setMigrationsDirectory($dirName);
        $configuration->registerMigrationsFromDirectory($dirName);

        $this->migrationConfiguration = $configuration;
    }

    /**
     * @return string
     * @throws OutOfBoundsException
     */
    private function getMigrationNamespace(): string
    {
        return $this->getMigrationProperty(self::MIGRATION_NAMESPACE_KEY);
    }

    /**
     * @return string
     * @throws OutOfBoundsException
     */
    private function getMigrationDirectory(): string
    {
        return $this->getMigrationProperty(self::MIGRATION_DIR_KEY);
    }

    /**
     * @return string
     * @throws OutOfBoundsException
     */
    private function getMigrationTable(): string
    {
        return $this->getMigrationProperty(self::MIGRATION_TABLE_KEY);
    }

    /**
     * @param $key
     * @return string
     * @throws OutOfBoundsException
     */
    private function getMigrationProperty($key): string
    {
        if (!key_exists($key, $this->doctrineMigrationConfig)) {
            throw new OutOfBoundsException(sprintf(self::ERR_MSG_MISSING_KEY, $key));
        }

        return $this->doctrineMigrationConfig[$key];
    }
}
