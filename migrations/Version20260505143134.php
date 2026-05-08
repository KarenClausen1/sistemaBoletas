<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505143134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE boletas (id INT AUTO_INCREMENT NOT NULL, numero_boleta VARCHAR(100) NOT NULL, expediente VARCHAR(13) DEFAULT NULL, profesional VARCHAR(30) NOT NULL, email_profesional VARCHAR(50) NOT NULL, estado VARCHAR(50) NOT NULL, responsable VARCHAR(50) DEFAULT NULL, drive_link LONGTEXT NOT NULL, observaciones LONGTEXT DEFAULT NULL, fecha_creacion DATETIME NOT NULL, fecha_actualizacion DATETIME NOT NULL, UNIQUE INDEX UNIQ_CECF18ACF9515018 (numero_boleta), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE boletas');
    }
}
