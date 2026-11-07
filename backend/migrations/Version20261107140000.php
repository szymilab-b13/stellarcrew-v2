<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261107140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[user-management] create user_management schema with user_profiles table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS user_management');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS user_management.user_profiles (
                id UUID NOT NULL,
                display_name VARCHAR(100) DEFAULT NULL,
                avatar_color VARCHAR(20) NOT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                deactivated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS user_management.user_profiles');
        $this->addSql('DROP SCHEMA IF EXISTS user_management CASCADE');
    }
}
