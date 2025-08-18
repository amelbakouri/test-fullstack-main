<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250817145240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression de clocking_project_id, FKs correctes, roles non-null, unique(user,date)';
    }

    public function up(Schema $schema): void
    {
        // --- 0) USER.ROLES : backfill + NOT NULL ---
        // Compatible MySQL/MariaDB (roles peut être JSON ou LONGTEXT)
        $this->addSql("UPDATE `user` SET roles='[\"ROLE_USER\"]' WHERE roles IS NULL OR roles='' OR roles='null'");
        // Si MySQL natif JSON : OK ; si MariaDB (JSON = LONGTEXT), c’est accepté aussi par Doctrine
        $this->addSql("ALTER TABLE `user` MODIFY roles JSON NOT NULL");

        // --- 1) CLOCKING: supprimer proprement clocking_project_id (FK + index + colonne) ---

        // 1.a) Drop FK qui pointe sur clocking_project_id si elle existe
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
            SET @s := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE clocking DROP FOREIGN KEY `', @fk, '`'), 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // 1.b) Drop index sur clocking_project_id si présent
        $this->addSql("
            SET @idx := (
              SELECT INDEX_NAME
              FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = 'clocking'
                AND COLUMN_NAME  = 'clocking_project_id'
              LIMIT 1
            );
            SET @s := IF(@idx IS NOT NULL, CONCAT('DROP INDEX `', @idx, '` ON clocking'), 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // 1.c) Drop colonne clocking_project_id si elle existe
        $this->addSql("
            SET @col := (
              SELECT 1 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = 'clocking'
                AND COLUMN_NAME  = 'clocking_project_id'
              LIMIT 1
            );
            SET @s := IF(@col = 1, 'ALTER TABLE clocking DROP COLUMN clocking_project_id', 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // --- 2) CLOCKING_ENTRY: remettre des FKs propres ---

        // 2.a) Drop FK clocking_entry.clocking_id si existante
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
            SET @s := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE clocking_entry DROP FOREIGN KEY `', @fk, '`'), 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // 2.b) Drop FK clocking_entry.project_id si existante
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
            SET @s := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE clocking_entry DROP FOREIGN KEY `', @fk, '`'), 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // 2.c) (Re)Créer les bonnes FKs
        // FK -> clocking(id) ON DELETE CASCADE (quand on supprime un pointage, on supprime ses lignes)
        $this->addSql("
            ALTER TABLE clocking_entry
              ADD CONSTRAINT FK_CLOCKING_ENTRY_CLOCKING
              FOREIGN KEY (clocking_id) REFERENCES clocking(id)
              ON DELETE CASCADE ON UPDATE CASCADE
        ");

        // FK -> project(id) RESTRICT (par défaut). Si tu préfères CASCADE/SET NULL, adapte ici.
        $this->addSql("
            ALTER TABLE clocking_entry
              ADD CONSTRAINT FK_CLOCKING_ENTRY_PROJECT
              FOREIGN KEY (project_id) REFERENCES project(id)
              ON DELETE RESTRICT ON UPDATE CASCADE
        ");

        // --- 3) CLOCKING: contrainte d’unicité (user, date) ---

        // Si un index du même nom existe déjà, ne pas planter (création conditionnelle)
        $this->addSql("
            SET @exists := (
              SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = 'clocking'
                AND INDEX_NAME   = 'uniq_user_date'
              LIMIT 1
            );
            SET @s := IF(@exists IS NULL,
              'CREATE UNIQUE INDEX uniq_user_date ON clocking (clocking_user_id, `date`)',
              'SELECT 1'
            );
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");
    }

    public function down(Schema $schema): void
    {
        // --- Revenir en arrière ---

        // 1) Supprimer l’unique (user, date) si présent
        $this->addSql("
            SET @exists := (
              SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = 'clocking'
                AND INDEX_NAME   = 'uniq_user_date'
              LIMIT 1
            );
            SET @s := IF(@exists = 1,
              'DROP INDEX uniq_user_date ON clocking',
              'SELECT 1'
            );
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // 2) CLOCKING_ENTRY : drop FKs ajoutées
        $this->addSql("
            SET @fk := (
              SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clocking_entry'
                AND COLUMN_NAME='clocking_id' AND REFERENCED_TABLE_NAME IS NOT NULL
              LIMIT 1
            );
            SET @s := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE clocking_entry DROP FOREIGN KEY `', @fk, '`'), 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");
        $this->addSql("
            SET @fk := (
              SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clocking_entry'
                AND COLUMN_NAME='project_id' AND REFERENCED_TABLE_NAME IS NOT NULL
              LIMIT 1
            );
            SET @s := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE clocking_entry DROP FOREIGN KEY `', @fk, '`'), 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // 3) CLOCKING : recréer l’ancienne colonne clocking_project_id (nullable) + son index + FK (SET NULL)
        // (seulement si elle n’existe plus)
        $this->addSql("
            SET @col := (
              SELECT 1 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = 'clocking'
                AND COLUMN_NAME  = 'clocking_project_id'
              LIMIT 1
            );
            SET @s := IF(@col IS NULL, 'ALTER TABLE clocking ADD COLUMN clocking_project_id INT NULL', 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // Index si absent
        $this->addSql("
            SET @idx := (
              SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = 'clocking'
                AND INDEX_NAME   = 'IDX_D3E9DCCD4431A71B'
              LIMIT 1
            );
            SET @s := IF(@idx IS NULL, 'CREATE INDEX IDX_D3E9DCCD4431A71B ON clocking (clocking_project_id)', 'SELECT 1');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        ");

        // FK (SET NULL)
        $this->addSql("
            ALTER TABLE clocking
              ADD CONSTRAINT FK_D3E9DCCD4431A71B
              FOREIGN KEY (clocking_project_id) REFERENCES project(id)
              ON DELETE SET NULL ON UPDATE CASCADE
        ");

        // 4) USER.ROLES : autoriser à nouveau NULL (revert strict)
        $this->addSql("ALTER TABLE `user` MODIFY roles JSON NULL");
    }
}
