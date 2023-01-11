<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renames "user::rank" to "user::handrank" because "rank" is a reserved word in MySQL 8.0+.
 */
final class Version20230111023433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renames "user::rank" to "user::handrank" because "rank" is a reserved word in MySQL 8.0+.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE card CHANGE `rank` handrank SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE card CHANGE handrank `rank` SMALLINT DEFAULT NULL');
    }
}
