<?php

namespace App\Entity;

use App\Repository\BoletaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoletaRepository::class)]
#[ORM\Table(name: 'boletas')]
#[ORM\HasLifecycleCallbacks]
class Boleta
{
    public const ESTADO_PENDIENTE  = 'pendiente';
    public const ESTADO_PAGADA = 'pagada';
    public const ESTADO_SUBIDA = 'subida';
    public const ESTADO_DETENIDA = 'detenida';

    public const ESTADOS = [
        'Pendiente'  => self::ESTADO_PENDIENTE,  //solo se cargo a la web
        'Pagada'     => self::ESTADO_PAGADA,     //se pago pero no se subio a drive
        'Subida'     => self::ESTADO_SUBIDA,     //se pago y se subio al drive
        'Detenida'   => self::ESTADO_DETENIDA,   //se pago pero se detuvo por algun motivo (ej: falta de datos, error en el pago, etc)
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $numeroBoleta;

    #[ORM\Column(type: Types::STRING, length: 13, nullable: true)]
    private ?string $expediente = null;

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $profesional;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $fechaVencimiento = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $emailProfesional;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $estado = self::ESTADO_PENDIENTE;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $driveLink;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observaciones = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $createdBy = null;

    // Campos de auditoría: mapeados en la base de datos
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $fechaCreacion;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $fechaActualizacion;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->fechaCreacion      = new \DateTimeImmutable();
        $this->fechaActualizacion = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->fechaActualizacion = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getNumeroBoleta(): string { return $this->numeroBoleta; }
    public function setNumeroBoleta(string $v): static { $this->numeroBoleta = $v; return $this; }

    public function getExpediente(): ?string { return $this->expediente; }
    public function setExpediente(?string $v): static { $this->expediente = $v; return $this; }

    public function getProfesional(): string { return $this->profesional; }
    public function setProfesional(string $v): static { $this->profesional = $v; return $this; }

    public function getFechaVencimiento(): ?\DateTimeImmutable { return $this->fechaVencimiento; }
    public function setFechaVencimiento(?\DateTimeImmutable $v): static { $this->fechaVencimiento = $v; return $this; }

    public function getEmailProfesional(): ?string { return $this->emailProfesional; }
    public function setEmailProfesional(?string $v): static { $this->emailProfesional = $v; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $v): static { $this->estado = $v; return $this; }

    public function getDriveLink(): ?string { return $this->driveLink; }
    public function setDriveLink(?string $v): static { $this->driveLink = $v; return $this; }

    public function getObservaciones(): ?string { return $this->observaciones; }
    public function setObservaciones(?string $v): static { $this->observaciones = $v; return $this; }

    public function getCreatedBy(): ?string { return $this->createdBy; }
    public function setCreatedBy(?string $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getFechaCreacion(): \DateTimeImmutable { return $this->fechaCreacion; }
    public function getFechaActualizacion(): \DateTimeImmutable { return $this->fechaActualizacion; }

    public function getEstadoLabel(): string
    {
        return array_flip(self::ESTADOS)[$this->estado] ?? $this->estado;
    }
}

