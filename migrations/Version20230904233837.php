<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Expands card-to-faction association from many-to-one to many-to-many.
 */
final class Version20230904233837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds support for multi-faction cards.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE card_gang (gang_id INT NOT NULL, card_id INT NOT NULL, INDEX IDX_A3B01C19266B5E (gang_id), INDEX IDX_A3B01C14ACC9A20 (card_id), PRIMARY KEY(gang_id, card_id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE card_gang ADD CONSTRAINT FK_A3B01C19266B5E FOREIGN KEY (gang_id) REFERENCES gang (id)');
        $this->addSql('ALTER TABLE card_gang ADD CONSTRAINT FK_A3B01C14ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
        $this->addSql('INSERT INTO card_gang (card_id, gang_id) (SELECT id, gang_id FROM card WHERE gang_id IS NOT NULL)');
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D39266B5E');
        $this->addSql('DROP INDEX IDX_161498D39266B5E ON card');
        $this->addSql('ALTER TABLE card DROP gang_id');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
