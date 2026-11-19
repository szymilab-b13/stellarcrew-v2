<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118160300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[chat] create chat.chat_thread_worker_sessions table (BL-CAR-004)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS chat.chat_thread_worker_sessions (
                thread_id UUID NOT NULL,
                worker_id UUID NOT NULL,
                claude_session_id UUID NOT NULL,
                last_message_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (thread_id, worker_id)
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_thread_worker_sessions_worker ON chat.chat_thread_worker_sessions (worker_id)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_chat_chat_thread_worker_sessions_claude_session ON chat.chat_thread_worker_sessions (claude_session_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS chat.uniq_chat_chat_thread_worker_sessions_claude_session');
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_thread_worker_sessions_worker');
        $this->addSql('DROP TABLE IF EXISTS chat.chat_thread_worker_sessions');
    }
}
