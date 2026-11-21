<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261121130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[workers] add workers.workers.host_info JSONB nullable (BL-WI-004)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE workers.workers
            ADD COLUMN IF NOT EXISTS host_info JSONB NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workers.workers DROP COLUMN IF EXISTS host_info');
    }
}
