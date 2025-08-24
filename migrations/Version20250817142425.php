<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250817142425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Nettoyage données + FKs robustes + contrainte d’unicité (user, date) sur clocking';
    }

    public function up(Schema $schema): void
    {
        // 0) --- USERS.ROLES : backfill & NOT NULL ---
        // Universal backfill (compatible MySQL/MariaDB)
        $this->addSql("UPDATE `user` SET roles='[\"ROLE_USER\"]' WHERE roles IS NULL OR roles='' OR roles='null'");
        // Force NOT NULL (si JSON natif MySQL, ça restera JSON ; si MariaDB, c’est LONGTEXT)
        $this->addSql("ALTER TABLE `user` MODIFY roles JSON NOT NULL");

        // 1) --- CLOCKING: nettoyer les projects invalides & rendre la colonne nullable ---
        $this->addSql("
            UPDATE clocking c
            LEFT JOIN project p ON p.id = c.clocking_project_id
            SET c.clocking_project_id = NULL
            WHERE c.clocking_project_id IS NOT NULL
              AND (c.clocking_project_id = 0 OR p.id IS NULL)
        ");
        $this->addSql("ALTER TABLE clocking MODIFY clocking_project_id INT NULL");

        // 2) --- DROP FKs existantes de manière SAFE (si présentes) ---
        // 2.a) drop FK sur clocking_user_id (nom auto Doctrine souvent FK_D3E9DCCDA1F846FC)
        $this->addSql("
            SET @fk := (
              SELECT CONSTRAINT_NAME
              FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'clocking'
                AND COLUMN_NAME = 'clocking_user_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
              LIMIT 1
            );
            SET @s := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE clocking DROP FOREIGN KEY ', @fk), 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // 2.b) drop FK sur clocking_project_id (souvent FK_D3E9DCCD4431A71B)
        $this->addSql("
            SET @fk := (
              SELECT CONSTRAINT_NAME
              FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'clocking'
                AND COLUMN_NAME = 'clocking_project_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
              LIMIT 1
            );
            SET @s := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE clocking DROP FOREIGN KEY ', @fk), 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // 2.c) drop FK clocking_entry.clocking_id (souvent FK_64D26B05B6D103F)
        $this->addSql("
            SET @fk := (
              SELECT CONSTRAINT_NAME
              FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'clocking_entry'
                AND COLUMN_NAME = 'clocking_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
              LIMIT 1
            );
            SET @s := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE clocking_entry DROP FOREIGN KEY ', @fk), 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // 2.d) drop FK clocking_entry.project_id
        $this->addSql("
            SET @fk := (
              SELECT CONSTRAINT_NAME
              FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'clocking_entry'
                AND COLUMN_NAME = 'project_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
              LIMIT 1
            );
            SET @s := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE clocking_entry DROP FOREIGN KEY ', @fk), 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALOCATE PREPARE stmt;
        ");

        // 3) --- Recréer les FKs correctement ---
        // 3.a) FK obligatoire vers user
        $this->addSql("
            ALTER TABLE clocking
              ADD CONSTRAINT FK_D3E9DCCDA1F846FC
              FOREIGN KEY (clocking_user_id) REFERENCES `user`(id)
              ON DELETE RESTRICT ON UPDATE CASCADE
        ");

        // 3.b) FK optionnelle vers project (SET NULL)
        $this->addSql("
            ALTER TABLE clocking
              ADD CONSTRAINT FK_D3E9DCCD4431A71B
              FOREIGN KEY (clocking_project_id) REFERENCES project(id)
              ON DELETE SET NULL ON UPDATE CASCADE
        ");

        // 3.c) FKs de clocking_entry
        $this->addSql("
            ALTER TABLE clocking_entry
              ADD CONSTRAINT FK_64D26B05B6D103F
              FOREIGN KEY (clocking_id) REFERENCES clocking(id)
              ON DELETE CASCADE ON UPDATE CASCADE
        ");
        $this->addSql("
            ALTER TABLE clocking_entry
              ADD CONSTRAINT FK_64D26B05166D1F9C
              FOREIGN KEY (project_id) REFERENCES project(id)
              ON DELETE RESTRICT ON UPDATE CASCADE
        ");

        // 4) --- Dédupliquer clocking avant l’unique (user, date) ---
        // regrouper par (user, date), garder le MIN(id), recoller les entries, supprimer le reste
        $this->addSql("DROP TEMPORARY TABLE IF EXISTS dup");
        $this->addSql("
            CREATE TEMPORARY TABLE dup AS
            SELECT clocking_user_id, `date`, MIN(id) AS keep_id
            FROM clocking
            GROUP BY clocking_user_id, `date`
            HAVING COUNT(*) > 1
        ");
        // Rebasculer les entries vers l'id conservé
        $this->addSql("
            UPDATE clocking_entry e
            JOIN clocking c ON c.id = e.clocking_id
            JOIN dup d ON d.clocking_user_id = c.clocking_user_id AND d.`date` = c.`date`
            SET e.clocking_id = d.keep_id
            WHERE c.id <> d.keep_id
        ");
        // Supprimer les clockings en trop
        $this->addSql("
            DELETE c FROM clocking c
            JOIN dup d ON d.clocking_user_id = c.clocking_user_id AND d.`date` = c.`date`
            WHERE c.id <> d.keep_id
        ");
        $this->addSql("DROP TEMPORARY TABLE IF EXISTS dup");


        $this->addSql("CREATE UNIQUE INDEX uniq_user_date ON clocking (clocking_user_id, `date`)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX uniq_user_date ON clocking");

        $this->addSql("ALTER TABLE clocking_entry DROP FOREIGN KEY FK_64D26B05B6D103F");
        $this->addSql("ALTER TABLE clocking_entry DROP FOREIGN KEY FK_64D26B05166D1F9C");
        $this->addSql("ALTER TABLE clocking DROP FOREIGN KEY FK_D3E9DCCD4431A71B");
        $this->addSql("ALTER TABLE clocking DROP FOREIGN KEY FK_D3E9DCCDA1F846FC");

        

       
    }
}
