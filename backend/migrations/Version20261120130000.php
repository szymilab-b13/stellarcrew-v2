<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261120130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[workers] add workers.workers.fingerprint_change_history JSONB (BL-WA-005c)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE workers.workers
            ADD COLUMN IF NOT EXISTS fingerprint_change_history JSONB NOT NULL DEFAULT '[]'::jsonb
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workers.workers DROP COLUMN IF EXISTS fingerprint_change_history');
    }
}
