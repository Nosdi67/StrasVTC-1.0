<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241003203325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chauffeur ADD utilisateur_id INT NOT NULL');
        $this->addSql('ALTER TABLE chauffeur ADD CONSTRAINT FK_5CA777B8FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5CA777B8FB88E14F ON chauffeur (utilisateur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chauffeur DROP FOREIGN KEY FK_5CA777B8FB88E14F');
        $this->addSql('DROP INDEX UNIQ_5CA777B8FB88E14F ON chauffeur');
        $this->addSql('ALTER TABLE chauffeur DROP utilisateur_id');
    }
}
