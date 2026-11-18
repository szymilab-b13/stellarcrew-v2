<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[workers] create workers.workers table (BL-CAR-001)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS workers');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS workers.workers (
                id UUID NOT NULL,
                owner_user_id UUID NOT NULL,
                name VARCHAR(80) NOT NULL,
                host_label VARCHAR(120) DEFAULT NULL,
                secret_token_hash VARCHAR(255) NOT NULL,
                rabbitmq_user VARCHAR(80) NOT NULL,
                last_heartbeat_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX uniq_workers_workers_rabbitmq_user ON workers.workers (rabbitmq_user)');
        $this->addSql('CREATE INDEX idx_workers_workers_owner_user_id ON workers.workers (owner_user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS workers.idx_workers_workers_owner_user_id');
        $this->addSql('DROP INDEX IF EXISTS workers.uniq_workers_workers_rabbitmq_user');
        $this->addSql('DROP TABLE IF EXISTS workers.workers');
    }
}
