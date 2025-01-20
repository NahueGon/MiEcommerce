<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205135753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clothing DROP size');
        $this->addSql('ALTER TABLE product DROP stock');
        $this->addSql('ALTER TABLE size ADD stock INT NOT NULL, CHANGE name size VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD stock INT DEFAULT 0');
        $this->addSql('ALTER TABLE clothing ADD size VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE size DROP stock, CHANGE size name VARCHAR(255) NOT NULL');
    }
}
