<?php

declare(strict_types=1);

namespace Ministryofjustice\DoctrineMigrationVersionCheckerTest;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use RuntimeException;

class TestableSchemaManager extends AbstractSchemaManager
{
    public function listTableNames()
    {
        return [];
    }

    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        throw new RuntimeException('Not implemented');
    }
}
