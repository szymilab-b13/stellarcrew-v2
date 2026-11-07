<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261108120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[auth] add users.deactivated_at for account deactivation flag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE auth.users
            ADD COLUMN IF NOT EXISTS deactivated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth.users DROP COLUMN IF EXISTS deactivated_at');
    }
}
