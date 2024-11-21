<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241108204833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accessory (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE accessory ADD CONSTRAINT FK_A1B1251CBF396750 FOREIGN KEY (id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE accesory');
        $this->addSql('ALTER TABLE clothing CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE clothing ADD CONSTRAINT FK_139C38B1BF396750 FOREIGN KEY (id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product ADD product_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE shoe CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE shoe ADD CONSTRAINT FK_C1B7A849BF396750 FOREIGN KEY (id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accesory (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE accessory DROP FOREIGN KEY FK_A1B1251CBF396750');
        $this->addSql('DROP TABLE accessory');
        $this->addSql('ALTER TABLE clothing DROP FOREIGN KEY FK_139C38B1BF396750');
        $this->addSql('ALTER TABLE clothing CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE shoe DROP FOREIGN KEY FK_C1B7A849BF396750');
        $this->addSql('ALTER TABLE shoe CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE product DROP product_type');
    }
}
