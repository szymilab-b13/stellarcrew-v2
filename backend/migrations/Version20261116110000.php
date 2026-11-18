<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[chat] create chat_messages envelope table with routing, priority, metadata, reply, classification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS chat.chat_messages (
                id UUID NOT NULL,
                session_id UUID NOT NULL,
                thread_id UUID NOT NULL,
                sender_type VARCHAR(20) NOT NULL,
                sender_id UUID NOT NULL,
                recipient_type VARCHAR(20) NOT NULL,
                recipient_id UUID NOT NULL,
                subject VARCHAR(120) NOT NULL,
                priority VARCHAR(4) NOT NULL,
                content TEXT NOT NULL,
                content_type VARCHAR(30) NOT NULL,
                metadata JSONB NOT NULL,
                reply_to_message_id UUID DEFAULT NULL,
                engine_id UUID NOT NULL,
                engine_type VARCHAR(30) NOT NULL,
                classified_by VARCHAR(20) NOT NULL,
                classification_confidence DOUBLE PRECISION DEFAULT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_messages_session_id ON chat.chat_messages (session_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_messages_thread_id ON chat.chat_messages (thread_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_messages_thread_created ON chat.chat_messages (thread_id, created_at DESC)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_messages_recipient ON chat.chat_messages (recipient_type, recipient_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_messages_engine ON chat.chat_messages (engine_type, engine_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_messages_reply_to ON chat.chat_messages (reply_to_message_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_messages_reply_to');
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_messages_engine');
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_messages_recipient');
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_messages_thread_created');
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_messages_thread_id');
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_messages_session_id');
        $this->addSql('DROP TABLE IF EXISTS chat.chat_messages');
    }
}
