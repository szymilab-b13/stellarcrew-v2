<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[workers] add fingerprint_hash + fingerprint_meta columns to workers.workers (BL-WA-001a)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workers.workers ADD COLUMN fingerprint_hash CHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE workers.workers ADD COLUMN fingerprint_meta JSONB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workers.workers DROP COLUMN IF EXISTS fingerprint_meta');
        $this->addSql('ALTER TABLE workers.workers DROP COLUMN IF EXISTS fingerprint_hash');
    }
}
