<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261115120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[agents] split agents.agents.instructions into 5 TEXT columns matching agent_templates schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agents.agents DROP COLUMN IF EXISTS instructions');
        $this->addSql("ALTER TABLE agents.agents ADD COLUMN IF NOT EXISTS role_description TEXT NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE agents.agents ADD COLUMN IF NOT EXISTS expertise TEXT NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE agents.agents ADD COLUMN IF NOT EXISTS workflow TEXT NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE agents.agents ADD COLUMN IF NOT EXISTS style TEXT NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE agents.agents ADD COLUMN IF NOT EXISTS output_format TEXT NOT NULL DEFAULT ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agents.agents DROP COLUMN IF EXISTS output_format');
        $this->addSql('ALTER TABLE agents.agents DROP COLUMN IF EXISTS style');
        $this->addSql('ALTER TABLE agents.agents DROP COLUMN IF EXISTS workflow');
        $this->addSql('ALTER TABLE agents.agents DROP COLUMN IF EXISTS expertise');
        $this->addSql('ALTER TABLE agents.agents DROP COLUMN IF EXISTS role_description');
        $this->addSql("ALTER TABLE agents.agents ADD COLUMN IF NOT EXISTS instructions TEXT NOT NULL DEFAULT ''");
    }
}
