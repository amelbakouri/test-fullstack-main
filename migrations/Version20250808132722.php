<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250808132722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE clocking_entry (id INT AUTO_INCREMENT NOT NULL, duration INT NOT NULL, project_id INT NOT NULL, clocking_id INT NOT NULL, INDEX IDX_64D26B05166D1F9C (project_id), INDEX IDX_64D26B05B6D103F (clocking_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE clocking_entry ADD CONSTRAINT FK_64D26B05166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE clocking_entry ADD CONSTRAINT FK_64D26B05B6D103F FOREIGN KEY (clocking_id) REFERENCES clocking (id)');
        $this->addSql('ALTER TABLE clocking DROP FOREIGN KEY FK_D3E9DCCD4431A71B');
        $this->addSql('DROP INDEX IDX_D3E9DCCD4431A71B ON clocking');
        $this->addSql('ALTER TABLE clocking DROP duration, DROP clocking_project_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clocking_entry DROP FOREIGN KEY FK_64D26B05166D1F9C');
        $this->addSql('ALTER TABLE clocking_entry DROP FOREIGN KEY FK_64D26B05B6D103F');
        $this->addSql('DROP TABLE clocking_entry');
        $this->addSql('ALTER TABLE clocking ADD duration INT NOT NULL, ADD clocking_project_id INT NOT NULL');
        $this->addSql('ALTER TABLE clocking ADD CONSTRAINT FK_D3E9DCCD4431A71B FOREIGN KEY (clocking_project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_D3E9DCCD4431A71B ON clocking (clocking_project_id)');
    }
}
