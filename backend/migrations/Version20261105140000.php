<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[auth] add optional display_name column to auth.users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE auth.users
                ADD COLUMN IF NOT EXISTS display_name VARCHAR(100) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth.users DROP COLUMN IF EXISTS display_name');
    }
}
