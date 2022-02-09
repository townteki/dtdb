<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial migration to set up a new schema from scratch.
 */
final class Version20220329000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Installs schema.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE access_token (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_B6A2DD685F37A13B (token), INDEX IDX_B6A2DD6819EB6921 (client_id), INDEX IDX_B6A2DD68A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE auth_code (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, redirect_uri LONGTEXT NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_5933D02C5F37A13B (token), INDEX IDX_5933D02C19EB6921 (client_id), INDEX IDX_5933D02CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE card (id INT AUTO_INCREMENT NOT NULL, pack_id INT DEFAULT NULL, type_id INT DEFAULT NULL, shooter_id INT DEFAULT NULL, gang_id INT DEFAULT NULL, ts DATETIME NOT NULL, code VARCHAR(5) NOT NULL, number SMALLINT NOT NULL, quantity SMALLINT NOT NULL, title VARCHAR(255) NOT NULL, keywords VARCHAR(255) DEFAULT NULL, text VARCHAR(1024) DEFAULT NULL, flavor VARCHAR(1024) DEFAULT NULL, illustrator VARCHAR(255) DEFAULT NULL, cost SMALLINT DEFAULT NULL, `rank` SMALLINT DEFAULT NULL, upkeep SMALLINT DEFAULT NULL, production SMALLINT DEFAULT NULL, bullets SMALLINT DEFAULT NULL, influence SMALLINT DEFAULT NULL, control SMALLINT DEFAULT NULL, wealth SMALLINT DEFAULT NULL, octgnid VARCHAR(255) DEFAULT NULL, INDEX IDX_161498D31919B217 (pack_id), INDEX IDX_161498D3C54C8C93 (type_id), INDEX IDX_161498D3F42D3895 (shooter_id), INDEX IDX_161498D39266B5E (gang_id), INDEX code_index (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, random_id VARCHAR(255) NOT NULL, redirect_uris LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', secret VARCHAR(255) NOT NULL, allowed_grant_types LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, decklist_id INT DEFAULT NULL, text LONGTEXT NOT NULL, creation DATETIME NOT NULL, INDEX IDX_9474526CA76ED395 (user_id), INDEX IDX_9474526CF4E9531B (decklist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cycle (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, number SMALLINT NOT NULL, INDEX code_index (code), INDEX number_index (number), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE deck (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, outfit_id INT DEFAULT NULL, last_pack_id INT DEFAULT NULL, parent_decklist_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, datecreation DATETIME NOT NULL, dateupdate DATETIME NOT NULL, description LONGTEXT DEFAULT NULL, problem VARCHAR(20) DEFAULT NULL, deck_size SMALLINT DEFAULT NULL, tags VARCHAR(4000) DEFAULT NULL, INDEX IDX_4FAC3637A76ED395 (user_id), INDEX IDX_4FAC3637AE96E385 (outfit_id), INDEX IDX_4FAC36377F958E5F (last_pack_id), INDEX IDX_4FAC36379FC5416B (parent_decklist_id), INDEX dateupdate_index (dateupdate), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE deckchange (id INT AUTO_INCREMENT NOT NULL, deck_id INT DEFAULT NULL, datecreation DATETIME NOT NULL, variation VARCHAR(1024) NOT NULL, saved TINYINT(1) NOT NULL, INDEX IDX_B32E853111948DC (deck_id), INDEX deck_saved_index (deck_id, saved), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE decklist (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, outfit_id INT DEFAULT NULL, gang_id INT DEFAULT NULL, last_pack_id INT DEFAULT NULL, parent_deck_id INT DEFAULT NULL, precedent_decklist_id INT DEFAULT NULL, tournament_id INT DEFAULT NULL, ts DATETIME NOT NULL, name VARCHAR(60) NOT NULL, prettyname VARCHAR(60) NOT NULL, rawdescription LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, creation DATETIME NOT NULL, signature VARCHAR(32) NOT NULL, nbvotes INT NOT NULL, nbfavorites INT NOT NULL, nbcomments INT NOT NULL, INDEX IDX_ED030EC6A76ED395 (user_id), INDEX IDX_ED030EC6AE96E385 (outfit_id), INDEX IDX_ED030EC69266B5E (gang_id), INDEX IDX_ED030EC67F958E5F (last_pack_id), INDEX IDX_ED030EC663513C9A (parent_deck_id), INDEX IDX_ED030EC6C386FA95 (precedent_decklist_id), INDEX IDX_ED030EC633D1A3E7 (tournament_id), INDEX creation_index (creation), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE favorite (decklist_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_68C58ED9F4E9531B (decklist_id), INDEX IDX_68C58ED9A76ED395 (user_id), PRIMARY KEY(decklist_id, user_id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vote (decklist_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5A108564F4E9531B (decklist_id), INDEX IDX_5A108564A76ED395 (user_id), PRIMARY KEY(decklist_id, user_id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE decklistslot (id INT AUTO_INCREMENT NOT NULL, decklist_id INT DEFAULT NULL, card_id INT DEFAULT NULL, quantity SMALLINT NOT NULL, start SMALLINT NOT NULL, INDEX IDX_2071B1F4E9531B (decklist_id), INDEX IDX_2071B14ACC9A20 (card_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE deckslot (id INT AUTO_INCREMENT NOT NULL, deck_id INT DEFAULT NULL, card_id INT DEFAULT NULL, quantity SMALLINT NOT NULL, start SMALLINT NOT NULL, INDEX IDX_5C5D6B9111948DC (deck_id), INDEX IDX_5C5D6B94ACC9A20 (card_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE gang (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, INDEX code_index (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE highlight (id INT AUTO_INCREMENT NOT NULL, decklist LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pack (id INT AUTO_INCREMENT NOT NULL, cycle_id INT DEFAULT NULL, code VARCHAR(10) NOT NULL, name VARCHAR(255) NOT NULL, released DATE DEFAULT NULL, size SMALLINT NOT NULL, number SMALLINT NOT NULL, INDEX IDX_97DE5E235EC1162 (cycle_id), INDEX released_index (released), INDEX code_index (code), INDEX number_index (number), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE refresh_token (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_C74F21955F37A13B (token), INDEX IDX_C74F219519EB6921 (client_id), INDEX IDX_C74F2195A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, card_id INT DEFAULT NULL, user_id INT DEFAULT NULL, datecreation DATETIME NOT NULL, dateupdate DATETIME NOT NULL, rawtext LONGTEXT NOT NULL, text LONGTEXT NOT NULL, nbvotes SMALLINT NOT NULL, INDEX IDX_794381C64ACC9A20 (card_id), INDEX IDX_794381C6A76ED395 (user_id), UNIQUE INDEX usercard_index (card_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reviewvote (review_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_1B4C90573E2E969B (review_id), INDEX IDX_1B4C9057A76ED395 (user_id), PRIMARY KEY(review_id, user_id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shooter (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, INDEX name_index (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE suit (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, INDEX name_index (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(60) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type (id INT AUTO_INCREMENT NOT NULL, suit_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_8CDE5729F27CB76F (suit_id), INDEX name_index (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, username_canonical VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, email_canonical VARCHAR(180) NOT NULL, enabled TINYINT(1) NOT NULL, salt VARCHAR(255) DEFAULT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', reputation INT DEFAULT NULL, gang VARCHAR(255) NOT NULL, creation DATETIME DEFAULT NULL, resume LONGTEXT DEFAULT NULL, role INT DEFAULT NULL, status INT DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, donation INT NOT NULL, notif_author TINYINT(1) DEFAULT \'1\' NOT NULL, notif_commenter TINYINT(1) DEFAULT \'1\' NOT NULL, notif_mention TINYINT(1) DEFAULT \'1\' NOT NULL, notif_follow TINYINT(1) DEFAULT \'1\' NOT NULL, notif_successor TINYINT(1) DEFAULT \'1\' NOT NULL, UNIQUE INDEX UNIQ_8D93D64992FC23A8 (username_canonical), UNIQUE INDEX UNIQ_8D93D649A0D96FBF (email_canonical), UNIQUE INDEX UNIQ_8D93D649C05FB297 (confirmation_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE follow (user_id INT NOT NULL, follower_id INT NOT NULL, INDEX IDX_68344470A76ED395 (user_id), INDEX IDX_68344470AC24F853 (follower_id), PRIMARY KEY(user_id, follower_id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE access_token ADD CONSTRAINT FK_B6A2DD6819EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE access_token ADD CONSTRAINT FK_B6A2DD68A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE auth_code ADD CONSTRAINT FK_5933D02C19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE auth_code ADD CONSTRAINT FK_5933D02CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D31919B217 FOREIGN KEY (pack_id) REFERENCES pack (id)');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D3C54C8C93 FOREIGN KEY (type_id) REFERENCES type (id)');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D3F42D3895 FOREIGN KEY (shooter_id) REFERENCES shooter (id)');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D39266B5E FOREIGN KEY (gang_id) REFERENCES gang (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF4E9531B FOREIGN KEY (decklist_id) REFERENCES decklist (id)');
        $this->addSql('ALTER TABLE deck ADD CONSTRAINT FK_4FAC3637A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE deck ADD CONSTRAINT FK_4FAC3637AE96E385 FOREIGN KEY (outfit_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE deck ADD CONSTRAINT FK_4FAC36377F958E5F FOREIGN KEY (last_pack_id) REFERENCES pack (id)');
        $this->addSql('ALTER TABLE deck ADD CONSTRAINT FK_4FAC36379FC5416B FOREIGN KEY (parent_decklist_id) REFERENCES decklist (id)');
        $this->addSql('ALTER TABLE deckchange ADD CONSTRAINT FK_B32E853111948DC FOREIGN KEY (deck_id) REFERENCES deck (id)');
        $this->addSql('ALTER TABLE decklist ADD CONSTRAINT FK_ED030EC6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE decklist ADD CONSTRAINT FK_ED030EC6AE96E385 FOREIGN KEY (outfit_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE decklist ADD CONSTRAINT FK_ED030EC69266B5E FOREIGN KEY (gang_id) REFERENCES gang (id)');
        $this->addSql('ALTER TABLE decklist ADD CONSTRAINT FK_ED030EC67F958E5F FOREIGN KEY (last_pack_id) REFERENCES pack (id)');
        $this->addSql('ALTER TABLE decklist ADD CONSTRAINT FK_ED030EC663513C9A FOREIGN KEY (parent_deck_id) REFERENCES deck (id)');
        $this->addSql('ALTER TABLE decklist ADD CONSTRAINT FK_ED030EC6C386FA95 FOREIGN KEY (precedent_decklist_id) REFERENCES decklist (id)');
        $this->addSql('ALTER TABLE decklist ADD CONSTRAINT FK_ED030EC633D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id)');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9F4E9531B FOREIGN KEY (decklist_id) REFERENCES decklist (id)');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564F4E9531B FOREIGN KEY (decklist_id) REFERENCES decklist (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE decklistslot ADD CONSTRAINT FK_2071B1F4E9531B FOREIGN KEY (decklist_id) REFERENCES decklist (id)');
        $this->addSql('ALTER TABLE decklistslot ADD CONSTRAINT FK_2071B14ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE deckslot ADD CONSTRAINT FK_5C5D6B9111948DC FOREIGN KEY (deck_id) REFERENCES deck (id)');
        $this->addSql('ALTER TABLE deckslot ADD CONSTRAINT FK_5C5D6B94ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE pack ADD CONSTRAINT FK_97DE5E235EC1162 FOREIGN KEY (cycle_id) REFERENCES cycle (id)');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F219519EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F2195A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C64ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reviewvote ADD CONSTRAINT FK_1B4C90573E2E969B FOREIGN KEY (review_id) REFERENCES review (id)');
        $this->addSql('ALTER TABLE reviewvote ADD CONSTRAINT FK_1B4C9057A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE type ADD CONSTRAINT FK_8CDE5729F27CB76F FOREIGN KEY (suit_id) REFERENCES suit (id)');
        $this->addSql('ALTER TABLE follow ADD CONSTRAINT FK_68344470A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE follow ADD CONSTRAINT FK_68344470AC24F853 FOREIGN KEY (follower_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE deck DROP FOREIGN KEY FK_4FAC3637AE96E385');
        $this->addSql('ALTER TABLE decklist DROP FOREIGN KEY FK_ED030EC6AE96E385');
        $this->addSql('ALTER TABLE decklistslot DROP FOREIGN KEY FK_2071B14ACC9A20');
        $this->addSql('ALTER TABLE deckslot DROP FOREIGN KEY FK_5C5D6B94ACC9A20');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C64ACC9A20');
        $this->addSql('ALTER TABLE access_token DROP FOREIGN KEY FK_B6A2DD6819EB6921');
        $this->addSql('ALTER TABLE auth_code DROP FOREIGN KEY FK_5933D02C19EB6921');
        $this->addSql('ALTER TABLE refresh_token DROP FOREIGN KEY FK_C74F219519EB6921');
        $this->addSql('ALTER TABLE pack DROP FOREIGN KEY FK_97DE5E235EC1162');
        $this->addSql('ALTER TABLE deckchange DROP FOREIGN KEY FK_B32E853111948DC');
        $this->addSql('ALTER TABLE decklist DROP FOREIGN KEY FK_ED030EC663513C9A');
        $this->addSql('ALTER TABLE deckslot DROP FOREIGN KEY FK_5C5D6B9111948DC');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CF4E9531B');
        $this->addSql('ALTER TABLE deck DROP FOREIGN KEY FK_4FAC36379FC5416B');
        $this->addSql('ALTER TABLE decklist DROP FOREIGN KEY FK_ED030EC6C386FA95');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9F4E9531B');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564F4E9531B');
        $this->addSql('ALTER TABLE decklistslot DROP FOREIGN KEY FK_2071B1F4E9531B');
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D39266B5E');
        $this->addSql('ALTER TABLE decklist DROP FOREIGN KEY FK_ED030EC69266B5E');
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D31919B217');
        $this->addSql('ALTER TABLE deck DROP FOREIGN KEY FK_4FAC36377F958E5F');
        $this->addSql('ALTER TABLE decklist DROP FOREIGN KEY FK_ED030EC67F958E5F');
        $this->addSql('ALTER TABLE reviewvote DROP FOREIGN KEY FK_1B4C90573E2E969B');
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D3F42D3895');
        $this->addSql('ALTER TABLE type DROP FOREIGN KEY FK_8CDE5729F27CB76F');
        $this->addSql('ALTER TABLE decklist DROP FOREIGN KEY FK_ED030EC633D1A3E7');
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D3C54C8C93');
        $this->addSql('ALTER TABLE access_token DROP FOREIGN KEY FK_B6A2DD68A76ED395');
        $this->addSql('ALTER TABLE auth_code DROP FOREIGN KEY FK_5933D02CA76ED395');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE deck DROP FOREIGN KEY FK_4FAC3637A76ED395');
        $this->addSql('ALTER TABLE decklist DROP FOREIGN KEY FK_ED030EC6A76ED395');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9A76ED395');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564A76ED395');
        $this->addSql('ALTER TABLE refresh_token DROP FOREIGN KEY FK_C74F2195A76ED395');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6A76ED395');
        $this->addSql('ALTER TABLE reviewvote DROP FOREIGN KEY FK_1B4C9057A76ED395');
        $this->addSql('ALTER TABLE follow DROP FOREIGN KEY FK_68344470A76ED395');
        $this->addSql('ALTER TABLE follow DROP FOREIGN KEY FK_68344470AC24F853');
        $this->addSql('DROP TABLE access_token');
        $this->addSql('DROP TABLE auth_code');
        $this->addSql('DROP TABLE card');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE cycle');
        $this->addSql('DROP TABLE deck');
        $this->addSql('DROP TABLE deckchange');
        $this->addSql('DROP TABLE decklist');
        $this->addSql('DROP TABLE favorite');
        $this->addSql('DROP TABLE vote');
        $this->addSql('DROP TABLE decklistslot');
        $this->addSql('DROP TABLE deckslot');
        $this->addSql('DROP TABLE gang');
        $this->addSql('DROP TABLE highlight');
        $this->addSql('DROP TABLE pack');
        $this->addSql('DROP TABLE refresh_token');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE reviewvote');
        $this->addSql('DROP TABLE shooter');
        $this->addSql('DROP TABLE suit');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('DROP TABLE type');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE follow');
    }
}
