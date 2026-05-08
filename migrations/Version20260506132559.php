<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506132559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE boletas ADD fecha_vencimiento DATE DEFAULT NULL, DROP responsable, DROP fecha_creacion, DROP fecha_actualizacion');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE boletas ADD responsable VARCHAR(50) DEFAULT NULL, ADD fecha_creacion DATETIME NOT NULL, ADD fecha_actualizacion DATETIME NOT NULL, DROP fecha_vencimiento');
    }
}
