<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[organizations] create organizations schema with organizations table and partial unique indexes on owner_user_id and slug';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS organizations');

        $this->addSql(<<<'SQL'
                CREATE TABLE IF NOT EXISTS organizations.organizations (
                    id UUID NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    slug VARCHAR(120) NOT NULL,
                    owner_user_id UUID NOT NULL,
                    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                    updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                    deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                    PRIMARY KEY (id)
                )
            SQL);

        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_organizations_organizations_owner_user_id_active'
            . ' ON organizations.organizations (owner_user_id) WHERE deleted_at IS NULL',
        );

        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_organizations_organizations_slug_active'
            . ' ON organizations.organizations (slug) WHERE deleted_at IS NULL',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS organizations.uniq_organizations_organizations_slug_active');
        $this->addSql('DROP INDEX IF EXISTS organizations.uniq_organizations_organizations_owner_user_id_active');
        $this->addSql('DROP TABLE IF EXISTS organizations.organizations');
    }
}
