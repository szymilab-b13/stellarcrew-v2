<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[agents] add agents.default_worker_id (BL-CAR-004)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agents.agents ADD COLUMN IF NOT EXISTS default_worker_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_agents_agents_default_worker_id ON agents.agents (default_worker_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS agents.idx_agents_agents_default_worker_id');
        $this->addSql('ALTER TABLE agents.agents DROP COLUMN IF EXISTS default_worker_id');
    }
}
