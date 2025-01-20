<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241207135612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE size_stock DROP FOREIGN KEY FK_9AE694572AD16370');
        $this->addSql('ALTER TABLE size_stock CHANGE shoe_id shoe_id INT NOT NULL');
        $this->addSql('ALTER TABLE size_stock ADD CONSTRAINT FK_9AE694572AD16370 FOREIGN KEY (shoe_id) REFERENCES shoe (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE size_stock DROP FOREIGN KEY FK_9AE694572AD16370');
        $this->addSql('ALTER TABLE size_stock CHANGE shoe_id shoe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE size_stock ADD CONSTRAINT FK_9AE694572AD16370 FOREIGN KEY (shoe_id) REFERENCES shoe (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
