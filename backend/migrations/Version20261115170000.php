<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261115170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[chat] create chat schema with chat_sessions table and indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS chat');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS chat.chat_sessions (
                id UUID NOT NULL,
                organization_id UUID NOT NULL,
                label VARCHAR(120) NOT NULL,
                started_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                ended_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_sessions_organization_id ON chat.chat_sessions (organization_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_sessions_org_started ON chat.chat_sessions (organization_id, started_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS chat.chat_sessions');
        $this->addSql('DROP SCHEMA IF EXISTS chat');
    }
}
