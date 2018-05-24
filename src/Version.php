<?php

namespace Minitryofjustice\DoctrineMigrationVersionChecker;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Minitryofjustice\DoctrineMigrationVersionChecker\Exception\Exception;
use Minitryofjustice\DoctrineMigrationVersionChecker\Exception\InvalidArgumentException;
use Minitryofjustice\DoctrineMigrationVersionChecker\Exception\OutOfBoundsException;

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

    /**
     * @var Connection
     */
    private $conn;

    /**
     * @var array
     */
    private $doctrineMigrationConfig;

    /**
     * @var Configuration
     */
    private $migrationConfiguration;

    /**
     * DBMigrationVersionService constructor.
     * @param Connection $connection
     * @param array $doctrineMigrationConfig
     *   in a usual setup comes from 'doctrine' => [ 'migrations_configuration' => ['orm_default' => []]
     * @throws InvalidArgumentException
     */
    public function __construct(Connection $connection, array $doctrineMigrationConfig)
    {
        $this->conn = $connection;
        $this->doctrineMigrationConfig = $doctrineMigrationConfig;
    }

    /**
     * @return string
     */
    public function getCurrentVersion()
    {
        return $this->getMigrationConfiguration()->getCurrentVersion();
    }

    /**
     * @return Configuration
     */
    private function getMigrationConfiguration()
    {
        if (is_null($this->migrationConfiguration)) {
            $this->initMigrationConfiguration();
        }

        return $this->migrationConfiguration;
    }

    /**
     * @throws Exception
     */
    private function initMigrationConfiguration()
    {
        try {
            $dirName = $this->getMigrationDirectory();

            $configuration = new Configuration($this->conn);
            $configuration->setMigrationsTableName($this->getMigrationTable());
            $configuration->setMigrationsNamespace($this->getMigrationNamespace());
            $configuration->setMigrationsDirectory($dirName);
            $configuration->registerMigrationsFromDirectory($dirName);

            $this->migrationConfiguration = $configuration;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @return string
     * @throws OutOfBoundsException
     */
    private function getMigrationNamespace()
    {
        return $this->getMigrationProperty(self::MIGRATION_NAMESPACE_KEY);
    }

    /**
     * @return string
     * @throws OutOfBoundsException
     */
    private function getMigrationDirectory()
    {
        return $this->getMigrationProperty(self::MIGRATION_DIR_KEY);
    }

    /**
     * @return string
     * @throws OutOfBoundsException
     */
    private function getMigrationTable()
    {
        return $this->getMigrationProperty(self::MIGRATION_TABLE_KEY);
    }

    /**
     * @param $key
     * @return string
     * @throws OutOfBoundsException
     */
    private function getMigrationProperty($key)
    {
        if (!key_exists($key, $this->doctrineMigrationConfig)) {
            throw new OutOfBoundsException(sprintf(
                self::ERR_MSG_MISSING_KEY, $key
            ));
        }

        return $this->doctrineMigrationConfig[$key];
    }
}
