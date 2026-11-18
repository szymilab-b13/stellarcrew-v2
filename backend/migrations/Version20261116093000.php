<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[chat] add owner_user_id to chat_sessions for per-user ACL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat.chat_sessions ADD COLUMN owner_user_id UUID NOT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_sessions_owner_user_id ON chat.chat_sessions (owner_user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_sessions_owner_user_id');
        $this->addSql('ALTER TABLE chat.chat_sessions DROP COLUMN IF EXISTS owner_user_id');
    }
}
