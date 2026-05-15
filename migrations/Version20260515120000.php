<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260515120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add local file storage and audit fields to boletas, remove legacy drive link.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE boletas DROP drive_link');
        $this->addSql('ALTER TABLE boletas MODIFY profesional VARCHAR(255) DEFAULT NULL, MODIFY email_profesional VARCHAR(255) DEFAULT NULL, MODIFY estado VARCHAR(30) NOT NULL');
        $this->addSql('ALTER TABLE boletas ADD archivo_original_nombre VARCHAR(255) DEFAULT NULL, ADD archivo_original_nombre_original VARCHAR(255) DEFAULT NULL, ADD archivo_original_mime_type VARCHAR(150) DEFAULT NULL, ADD comprobante_nombre VARCHAR(255) DEFAULT NULL, ADD comprobante_nombre_original VARCHAR(255) DEFAULT NULL, ADD comprobante_mime_type VARCHAR(150) DEFAULT NULL, ADD updated_by LONGTEXT DEFAULT NULL, ADD fecha_cambio_estado DATETIME DEFAULT NULL, ADD usuario_cambio_estado LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE boletas MODIFY profesional VARCHAR(30) NOT NULL, MODIFY email_profesional VARCHAR(50) NOT NULL, MODIFY estado VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE boletas ADD drive_link LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE boletas DROP archivo_original_nombre, DROP archivo_original_nombre_original, DROP archivo_original_mime_type, DROP comprobante_nombre, DROP comprobante_nombre_original, DROP comprobante_mime_type, DROP updated_by, DROP fecha_cambio_estado, DROP usuario_cambio_estado');
    }
}

