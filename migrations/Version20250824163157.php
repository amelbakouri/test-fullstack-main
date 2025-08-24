<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250824163157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend la migration tolérante : FKs détectées dynamiquement, ON DELETE CASCADE sur clocking_entry.clocking_id, et colonne user.roles sans DEFAULT.';
    }

    public function up(Schema $schema): void
    {
        // --- 1) Corriger la colonne user.roles (pas de DEFAULT sur JSON/TEXT/BLOB) ---

        // Backfill: remplacer les NULL/vides par un JSON vide
        // (JSON_ARRAY() fonctionne en MySQL 8 ; en MariaDB, la valeur sera stockée en texte '[]')
        $this->addSql("UPDATE `user` SET `roles` = JSON_ARRAY() WHERE `roles` IS NULL OR `roles` = ''");

        // Essayer JSON NOT NULL (MySQL 8) ; sinon fallback LONGTEXT NOT NULL COMMENT '(DC2Type:json)' (MariaDB)
        try {
            $this->connection->executeStatement("ALTER TABLE `user` MODIFY `roles` JSON NOT NULL");
        } catch (\Throwable $e) {
            $this->connection->executeStatement("ALTER TABLE `user` MODIFY `roles` LONGTEXT NOT NULL COMMENT '(DC2Type:json)'");
        }

        // --- 2) Réparer les FKs de clocking_entry de manière sûre (drop si existent, puis add propres) ---

        // a) FK sur clocking_entry.clocking_id -> clocking.id (on veut ON DELETE CASCADE)
        $fkClocking = $this->connection->fetchOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'clocking_entry'
              AND COLUMN_NAME = 'clocking_id'
              AND REFERENCED_TABLE_NAME = 'clocking'
            LIMIT 1
        ");
        if ($fkClocking) {
            $this->addSql(sprintf("ALTER TABLE `clocking_entry` DROP FOREIGN KEY `%s`", $fkClocking));
        }
        $this->addSql("
            ALTER TABLE `clocking_entry`
            ADD CONSTRAINT `FK_64D26B05B6D103F`
            FOREIGN KEY (`clocking_id`) REFERENCES `clocking` (`id`) ON DELETE CASCADE
        ");

        // b) FK sur clocking_entry.project_id -> project.id
        $fkProject = $this->connection->fetchOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'clocking_entry'
              AND COLUMN_NAME = 'project_id'
              AND REFERENCED_TABLE_NAME = 'project'
            LIMIT 1
        ");
        if ($fkProject) {
            $this->addSql(sprintf("ALTER TABLE `clocking_entry` DROP FOREIGN KEY `%s`", $fkProject));
        }
        $this->addSql("
            ALTER TABLE `clocking_entry`
            ADD CONSTRAINT `FK_64D26B05166D1F9C`
            FOREIGN KEY (`project_id`) REFERENCES `project` (`id`)
        ");

        // --- 3) FK clocking.clocking_user_id -> user.id (on ne casse rien si elle existe déjà) ---

        // Si une FK existe déjà, on la laisse tranquille. Sinon on l'ajoute.
        $fkUser = $this->connection->fetchOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'clocking'
              AND COLUMN_NAME = 'clocking_user_id'
              AND REFERENCED_TABLE_NAME = 'user'
            LIMIT 1
        ");
        if (!$fkUser) {
            $this->addSql("
                ALTER TABLE `clocking`
                ADD CONSTRAINT `FK_D3E9DCCDA1F846FC`
                FOREIGN KEY (`clocking_user_id`) REFERENCES `user` (`id`)
            ");
        }
    }

    public function down(Schema $schema): void
    {
        // --- Revenir en arrière prudemment ---

        // 1) Retirer nos FKs ajoutées (si elles existent)
        $fkNames = [
            'clocking_entry' => ['FK_64D26B05B6D103F', 'FK_64D26B05166D1F9C'],
            'clocking'       => ['FK_D3E9DCCDA1F846FC'],
        ];

        foreach ($fkNames as $table => $names) {
            foreach ($names as $name) {
                $exists = $this->connection->fetchOne("
                    SELECT COUNT(*)
                    FROM information_schema.REFERENTIAL_CONSTRAINTS
                    WHERE CONSTRAINT_SCHEMA = DATABASE()
                      AND CONSTRAINT_NAME = :name
                ", ['name' => $name]);
                if ((int)$exists > 0) {
                    $this->addSql(sprintf("ALTER TABLE `%s` DROP FOREIGN KEY `%s`", $table, $name));
                }
            }
        }

        // 2) Rendre user.roles nullable (pas de DEFAULT non plus lors du down)
        try {
            $this->connection->executeStatement("ALTER TABLE `user` MODIFY `roles` JSON NULL");
        } catch (\Throwable $e) {
            $this->connection->executeStatement("ALTER TABLE `user` MODIFY `roles` LONGTEXT NULL COMMENT '(DC2Type:json)'");
        }
    }
}
