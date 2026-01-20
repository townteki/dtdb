<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds "is Wild West Edition" flag to deckslists
 */
final class Version20230702163723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds "is Wild West Edition" flag to deckslists.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE decklist ADD is_wwe TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE decklist DROP is_wwe');
    }
}
