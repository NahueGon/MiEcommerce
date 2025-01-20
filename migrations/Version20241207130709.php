<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241207130709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE size_stock ADD shoe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE size_stock ADD CONSTRAINT FK_9AE694572AD16370 FOREIGN KEY (shoe_id) REFERENCES shoe (id)');
        $this->addSql('CREATE INDEX IDX_9AE694572AD16370 ON size_stock (shoe_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE size_stock DROP FOREIGN KEY FK_9AE694572AD16370');
        $this->addSql('DROP INDEX IDX_9AE694572AD16370 ON size_stock');
        $this->addSql('ALTER TABLE size_stock DROP shoe_id');
    }
}
