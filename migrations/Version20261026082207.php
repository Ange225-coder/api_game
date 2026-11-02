<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261026082207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, type VARCHAR(128) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE game_creator (game_id INT NOT NULL, creator_id INT NOT NULL, INDEX IDX_29C4954CE48FD905 (game_id), INDEX IDX_29C4954C61220EA6 (creator_id), PRIMARY KEY (game_id, creator_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE game_creator ADD CONSTRAINT FK_29C4954CE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_creator ADD CONSTRAINT FK_29C4954C61220EA6 FOREIGN KEY (creator_id) REFERENCES creator (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_creator DROP FOREIGN KEY FK_29C4954CE48FD905');
        $this->addSql('ALTER TABLE game_creator DROP FOREIGN KEY FK_29C4954C61220EA6');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE game_creator');
    }
}
