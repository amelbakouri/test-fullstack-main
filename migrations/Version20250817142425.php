<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250817142425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clocking DROP FOREIGN KEY fk_clocking_user');
        $this->addSql('ALTER TABLE clocking ADD CONSTRAINT FK_D3E9DCCD4431A71B FOREIGN KEY (clocking_project_id) REFERENCES project (id)');
        $this->addSql('CREATE INDEX IDX_D3E9DCCD4431A71B ON clocking (clocking_project_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_date ON clocking (clocking_user_id, date)');
        $this->addSql('ALTER TABLE clocking_entry DROP FOREIGN KEY fk_entry_clocking');
        $this->addSql('ALTER TABLE clocking_entry DROP FOREIGN KEY fk_entry_project');
        $this->addSql('ALTER TABLE clocking_entry DROP FOREIGN KEY FK_64D26B05B6D103F');
        $this->addSql('ALTER TABLE clocking_entry ADD CONSTRAINT FK_64D26B05B6D103F FOREIGN KEY (clocking_id) REFERENCES clocking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON DEFAULT \'[]\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clocking DROP FOREIGN KEY FK_D3E9DCCD4431A71B');
        $this->addSql('DROP INDEX IDX_D3E9DCCD4431A71B ON clocking');
        $this->addSql('DROP INDEX uniq_user_date ON clocking');
        $this->addSql('ALTER TABLE clocking ADD CONSTRAINT fk_clocking_user FOREIGN KEY (clocking_user_id) REFERENCES user (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE clocking_entry DROP FOREIGN KEY FK_64D26B05B6D103F');
        $this->addSql('ALTER TABLE clocking_entry ADD CONSTRAINT fk_entry_clocking FOREIGN KEY (clocking_id) REFERENCES clocking (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE clocking_entry ADD CONSTRAINT fk_entry_project FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE clocking_entry ADD CONSTRAINT FK_64D26B05B6D103F FOREIGN KEY (clocking_id) REFERENCES clocking (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE `user` CHANGE roles roles JSON NOT NULL');
    }
}
