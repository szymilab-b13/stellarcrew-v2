<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[chat] add parent_thread_id to chat_threads for thread hierarchy (BL-CAGU-001)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat.chat_threads ADD COLUMN IF NOT EXISTS parent_thread_id UUID DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE chat.chat_threads
            ADD CONSTRAINT fk_chat_threads_parent
            FOREIGN KEY (parent_thread_id) REFERENCES chat.chat_threads(id)
        SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_threads_parent_thread_id ON chat.chat_threads (parent_thread_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_threads_parent_thread_id');
        $this->addSql('ALTER TABLE chat.chat_threads DROP CONSTRAINT IF EXISTS fk_chat_threads_parent');
        $this->addSql('ALTER TABLE chat.chat_threads DROP COLUMN IF EXISTS parent_thread_id');
    }
}
