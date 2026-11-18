<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[chat] create chat_threads table with session FK index and (session_id, created_at) composite';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS chat.chat_threads (
                id UUID NOT NULL,
                session_id UUID NOT NULL,
                subject VARCHAR(120) NOT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_threads_session_id ON chat.chat_threads (session_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_threads_session_created ON chat.chat_threads (session_id, created_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_threads_session_created');
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_threads_session_id');
        $this->addSql('DROP TABLE IF EXISTS chat.chat_threads');
    }
}
