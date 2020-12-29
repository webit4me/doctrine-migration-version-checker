<?php

declare(strict_types=1);

namespace Ministryofjustice\DoctrineMigrationVersionChecker;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use InvalidArgumentException;
use OutOfBoundsException;

class Version
{
    /**
     * @see default keys under migrations_configuration, orm_default at
     *   https://github.com/doctrine/DoctrineORMModule/blob/3.1.x/config/module.config.php#L158
     */
    const TABLE_CONFIG_SECTION_KEY = 'table_storage';
    const MIGRATION_TABLE_KEY = 'table_name';
    const MIGRATION_PATHS_SECTION_KEY = 'migrations_paths';

    const ERR_MSG_MISSING_KEY = 'Expected key "%s" is missing in the doctrine\'s migration configuration!';

    private Connection $conn;

    /**
     * @var array<mixed>
     */
    private array $doctrineMigrationConfig;

    private ?DependencyFactory $dependencyFactory = null;

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
    }

    public function getCurrentVersion(): string
    {
        return strval($this->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('current'));
    }

    private function getDependencyFactory(): DependencyFactory
    {
        if (is_null($this->dependencyFactory)) {
            $this->initMigrationConfiguration();
        }

        return $this->dependencyFactory;
    }

    private function initMigrationConfiguration(): void
    {
        $migrationTable = $this->getMigrationTable();
        $migrationPaths = $this->getMigrationPaths();

        $storageConfiguration = new TableMetadataStorageConfiguration();
        $storageConfiguration->setTableName($migrationTable);

        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration($storageConfiguration);

        foreach ($migrationPaths as $namespace => $path)
        {
            $configuration->addMigrationsDirectory($namespace, $path);
        }

        $this->dependencyFactory = DependencyFactory::fromConnection(new ExistingConfiguration($configuration), new ExistingConnection($this->conn));
    }

    /**
     * @return array<string, string>
     * @throws OutOfBoundsException
     */
    private function getMigrationPaths(): array
    {
        return $this->getArrayEntry(self::MIGRATION_PATHS_SECTION_KEY, $this->doctrineMigrationConfig);
    }

    /**
     * @return string
     * @throws OutOfBoundsException
     */
    private function getMigrationTable(): string
    {
        return $this->getArrayEntry(
            self::MIGRATION_TABLE_KEY,
            $this->getArrayEntry(
                self::TABLE_CONFIG_SECTION_KEY,
                $this->doctrineMigrationConfig
            )
        );
    }

    /**
     * @param string $key
     * @param array $array
     * @return string|array<mixed>
     */
    private function getArrayEntry(string $key, array $array)
    {
        if (!key_exists($key, $array)) {
            throw new OutOfBoundsException(sprintf(self::ERR_MSG_MISSING_KEY, $key));
        }

        return $array[$key];
    }
}
