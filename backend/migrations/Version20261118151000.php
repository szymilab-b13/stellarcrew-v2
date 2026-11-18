<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118151000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[workers] create workers.worker_pairing_codes table (BL-CAR-002)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS workers.worker_pairing_codes (
                code VARCHAR(9) NOT NULL,
                owner_user_id UUID NOT NULL,
                expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                claimed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (code)
            )
        SQL);

        $this->addSql('CREATE INDEX idx_workers_worker_pairing_codes_owner ON workers.worker_pairing_codes (owner_user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS workers.idx_workers_worker_pairing_codes_owner');
        $this->addSql('DROP TABLE IF EXISTS workers.worker_pairing_codes');
    }
}
