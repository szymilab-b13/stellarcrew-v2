<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118160200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[chat] add chat_messages.handled_by_worker_id (BL-CAR-004)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat.chat_messages ADD COLUMN IF NOT EXISTS handled_by_worker_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_chat_messages_handled_by_worker_id ON chat.chat_messages (handled_by_worker_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS chat.idx_chat_chat_messages_handled_by_worker_id');
        $this->addSql('ALTER TABLE chat.chat_messages DROP COLUMN IF EXISTS handled_by_worker_id');
    }
}
