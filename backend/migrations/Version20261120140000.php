<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261120140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[workers] drop workers.workers.secret_token_hash (BL-WA-009b legacy auth removal)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workers.workers DROP COLUMN IF EXISTS secret_token_hash');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE workers.workers
            ADD COLUMN secret_token_hash VARCHAR(255) NOT NULL DEFAULT ''
        SQL);
        $this->addSql('ALTER TABLE workers.workers ALTER COLUMN secret_token_hash DROP DEFAULT');
    }
}
