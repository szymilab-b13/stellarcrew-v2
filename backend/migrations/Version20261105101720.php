<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105101720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[core] create core schema, users and system_settings tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS core');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS core.users (
                id UUID NOT NULL,
                email VARCHAR(254) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_core_users_email ON core.users (email)');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS core.system_settings (
                name VARCHAR(100) NOT NULL,
                value TEXT DEFAULT NULL,
                updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (name)
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS core.system_settings');
        $this->addSql('DROP INDEX IF EXISTS core.uniq_core_users_email');
        $this->addSql('DROP TABLE IF EXISTS core.users');
    }
}
