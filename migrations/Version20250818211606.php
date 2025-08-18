<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250818211606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Nettoyage des FKs dupliquées sur clocking_entry, rétablissement des contraintes uniques et options ON DELETE/UPDATE cohérentes.';
    }

    public function up(Schema $schema): void
    {
        // --- CLOCKING: assurer ON UPDATE CASCADE (et pas de ON DELETE CASCADE)
        $this->addSql('ALTER TABLE `clocking` DROP FOREIGN KEY `FK_D3E9DCCDA1F846FC`');
        $this->addSql('ALTER TABLE `clocking` ADD CONSTRAINT `FK_D3E9DCCDA1F846FC` FOREIGN KEY (`clocking_user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE');

        // --- CLOCKING_ENTRY: supprimer les FKs dupliquées puis recréer 2 FKs propres

        // Supprime UNIQUEMENT les doublons "fk_entry_*" s'ils existent
        // (et on redéclare ensuite les "officielles" avec les bonnes options)
        $this->addSql('ALTER TABLE `clocking_entry` DROP FOREIGN KEY `fk_entry_clocking`');
        $this->addSql('ALTER TABLE `clocking_entry` DROP FOREIGN KEY `fk_entry_project`');

        // Par sécurité, on (re)drop aussi les FKs officielles si elles existent,
        // pour les recréer avec les bons ON DELETE/UPDATE.
        $this->addSql('ALTER TABLE `clocking_entry` DROP FOREIGN KEY `FK_CLOCKING_ENTRY_CLOCKING`');
        $this->addSql('ALTER TABLE `clocking_entry` DROP FOREIGN KEY `FK_CLOCKING_ENTRY_PROJECT`');

        // Recrée la FK vers clocking: ON DELETE CASCADE (supprimer un pointage supprime ses lignes) + ON UPDATE CASCADE
        $this->addSql('
            ALTER TABLE `clocking_entry`
            ADD CONSTRAINT `FK_CLOCKING_ENTRY_CLOCKING`
            FOREIGN KEY (`clocking_id`) REFERENCES `clocking` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ');

        // Recrée la FK vers project: RESTRICT par défaut à la suppression + ON UPDATE CASCADE
        $this->addSql('
            ALTER TABLE `clocking_entry`
            ADD CONSTRAINT `FK_CLOCKING_ENTRY_PROJECT`
            FOREIGN KEY (`project_id`) REFERENCES `project` (`id`)
            ON DELETE RESTRICT ON UPDATE CASCADE
        ');

        // --- USER.ROLES: si tu souhaites un défaut JSON [], garde cette ligne.
        // Attention: certaines versions MariaDB ne supportent pas DEFAULT sur JSON.
        $this->addSql("ALTER TABLE `user` MODIFY `roles` JSON NOT NULL");
        // Si tu veux un default [] et que ta version MySQL le supporte, remets:
        // $this->addSql("ALTER TABLE `user` MODIFY `roles` JSON NOT NULL DEFAULT (JSON_ARRAY())");
    }

    public function down(Schema $schema): void
    {
        // Revenir à la situation précédente (avec les anciens noms doublons)

        // CLOCKING FK
        $this->addSql('ALTER TABLE `clocking` DROP FOREIGN KEY `FK_D3E9DCCDA1F846FC`');
        $this->addSql('ALTER TABLE `clocking` ADD CONSTRAINT `FK_D3E9DCCDA1F846FC` FOREIGN KEY (`clocking_user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE');

        // CLOCKING_ENTRY: drop des FKs propres
        $this->addSql('ALTER TABLE `clocking_entry` DROP FOREIGN KEY `FK_CLOCKING_ENTRY_CLOCKING`');
        $this->addSql('ALTER TABLE `clocking_entry` DROP FOREIGN KEY `FK_CLOCKING_ENTRY_PROJECT`');

        // Récréation des FKs (ancienne situation avec doublons)
        $this->addSql('
            ALTER TABLE `clocking_entry`
            ADD CONSTRAINT `FK_CLOCKING_ENTRY_CLOCKING`
            FOREIGN KEY (`clocking_id`) REFERENCES `clocking` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ');
        $this->addSql('
            ALTER TABLE `clocking_entry`
            ADD CONSTRAINT `FK_CLOCKING_ENTRY_PROJECT`
            FOREIGN KEY (`project_id`) REFERENCES `project` (`id`)
            ON DELETE RESTRICT ON UPDATE CASCADE
        ');

        // (Ré)ajout des doublons si tu veux un down strictement identique à l’état initial
        $this->addSql('
            ALTER TABLE `clocking_entry`
            ADD CONSTRAINT `fk_entry_clocking`
            FOREIGN KEY (`clocking_id`) REFERENCES `clocking` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ');
        $this->addSql('
            ALTER TABLE `clocking_entry`
            ADD CONSTRAINT `fk_entry_project`
            FOREIGN KEY (`project_id`) REFERENCES `project` (`id`)
            ON DELETE RESTRICT ON UPDATE CASCADE
        ');

        // USER.ROLES : on revient à NOT NULL sans default explicite
        $this->addSql("ALTER TABLE `user` MODIFY `roles` JSON NOT NULL");
    }
}
