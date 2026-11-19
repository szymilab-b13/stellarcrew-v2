<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[workers] create workers.worker_refresh_tokens table (BL-WA-004b)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS workers.worker_refresh_tokens (
                token_hash CHAR(64) NOT NULL,
                worker_id UUID NOT NULL,
                expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                consumed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (token_hash),
                CONSTRAINT fk_worker_refresh_tokens_worker
                    FOREIGN KEY (worker_id) REFERENCES workers.workers (id) ON DELETE CASCADE
            )
        SQL);

        $this->addSql('CREATE INDEX idx_workers_worker_refresh_tokens_worker_id ON workers.worker_refresh_tokens (worker_id)');
        $this->addSql('CREATE INDEX idx_workers_worker_refresh_tokens_expires_at ON workers.worker_refresh_tokens (expires_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS workers.idx_workers_worker_refresh_tokens_expires_at');
        $this->addSql('DROP INDEX IF EXISTS workers.idx_workers_worker_refresh_tokens_worker_id');
        $this->addSql('DROP TABLE IF EXISTS workers.worker_refresh_tokens');
    }
}
