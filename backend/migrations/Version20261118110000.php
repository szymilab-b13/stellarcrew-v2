<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[agents] add agents.agents.color column (BL-CAGU-009a)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agents.agents ADD COLUMN IF NOT EXISTS color VARCHAR(7) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agents.agents DROP COLUMN IF EXISTS color');
    }
}
