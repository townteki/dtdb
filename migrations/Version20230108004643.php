<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the is_multiple column to the card table.
 */
final class Version20230108004643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the is_multiple column to the card table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE card ADD is_multiple TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE card DROP is_multiple');
    }
}
