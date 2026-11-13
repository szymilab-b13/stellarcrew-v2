<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[agents] create agent_members pivot table with unique (agent_id, user_id) constraint';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
                CREATE TABLE IF NOT EXISTS agents.agent_members (
                    id UUID NOT NULL,
                    agent_id UUID NOT NULL,
                    user_id UUID NOT NULL,
                    granted_by UUID NOT NULL,
                    granted_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                    PRIMARY KEY (id)
                )
            SQL);

        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_agents_agent_members_agent_user'
            . ' ON agents.agent_members (agent_id, user_id)',
        );

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_agents_agent_members_user_id ON agents.agent_members (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS agents.idx_agents_agent_members_user_id');
        $this->addSql('DROP INDEX IF EXISTS agents.uniq_agents_agent_members_agent_user');
        $this->addSql('DROP TABLE IF EXISTS agents.agent_members');
    }
}
