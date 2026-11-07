<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261108000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[audit] create audit schema with audit_log table and indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS audit');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS audit.audit_log (
                id UUID NOT NULL,
                actor_user_id UUID NOT NULL,
                action VARCHAR(100) NOT NULL,
                resource_type VARCHAR(50) DEFAULT NULL,
                resource_id VARCHAR(50) DEFAULT NULL,
                before_state JSONB DEFAULT NULL,
                after_state JSONB DEFAULT NULL,
                metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
                occurred_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                undone_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                undone_by UUID DEFAULT NULL,
                reverts_entry_id UUID DEFAULT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE INDEX idx_audit_log_actor_time ON audit.audit_log (actor_user_id, occurred_at DESC)');
        $this->addSql('CREATE INDEX idx_audit_log_action_time ON audit.audit_log (action, occurred_at DESC)');
        $this->addSql('CREATE INDEX idx_audit_log_resource_time ON audit.audit_log (resource_type, resource_id, occurred_at DESC)');
        $this->addSql('CREATE INDEX idx_audit_log_occurred_at ON audit.audit_log (occurred_at DESC)');
        $this->addSql('CREATE INDEX idx_audit_log_undone_at ON audit.audit_log (undone_at) WHERE undone_at IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS audit.audit_log');
        $this->addSql('DROP SCHEMA IF EXISTS audit CASCADE');
    }
}
