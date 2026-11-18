<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[chat] create chat_thread_summaries stub table for CHAT-BRAIN (BL-CAGU-002)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS chat.chat_thread_summaries (
                thread_id UUID NOT NULL,
                short_description TEXT DEFAULT NULL,
                summary_json JSONB DEFAULT NULL,
                messages_covered_until_id UUID DEFAULT NULL,
                last_generated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                generator_version VARCHAR(50) DEFAULT NULL,
                PRIMARY KEY (thread_id)
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE chat.chat_thread_summaries
            ADD CONSTRAINT fk_chat_thread_summaries_thread
            FOREIGN KEY (thread_id) REFERENCES chat.chat_threads(id)
            ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE chat.chat_thread_summaries
            ADD CONSTRAINT fk_chat_thread_summaries_messages_covered_until
            FOREIGN KEY (messages_covered_until_id) REFERENCES chat.chat_messages(id)
            ON DELETE SET NULL
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_thread_summaries_messages_covered_until_id ON chat.chat_thread_summaries (messages_covered_until_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_thread_summaries_messages_covered_until_id');
        $this->addSql('ALTER TABLE chat.chat_thread_summaries DROP CONSTRAINT IF EXISTS fk_chat_thread_summaries_messages_covered_until');
        $this->addSql('ALTER TABLE chat.chat_thread_summaries DROP CONSTRAINT IF EXISTS fk_chat_thread_summaries_thread');
        $this->addSql('DROP TABLE IF EXISTS chat.chat_thread_summaries');
    }
}
