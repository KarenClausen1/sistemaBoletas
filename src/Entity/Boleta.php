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
    // =====================================================
    // ESTADOS
    // =====================================================

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PAGADA = 'pagada';
    public const ESTADO_VENCIDA = 'vencida';

    // =====================================================
    // ESTADOS DISPONIBLES
    // =====================================================

    public const ESTADOS = [
        'Pendiente' => self::ESTADO_PENDIENTE,
        'Pagada' => self::ESTADO_PAGADA,
        'Vencida' => self::ESTADO_VENCIDA,
    ];

    // =====================================================
    // COMPATIBILIDAD LEGACY
    // =====================================================

    public const ESTADOS_LEGACY = [
        'en_proceso' => self::ESTADO_PENDIENTE,
        'detenida' => self::ESTADO_PENDIENTE,
        'subida' => self::ESTADO_PAGADA,
        'comprobante_subido' => self::ESTADO_PAGADA,
        'finalizada' => self::ESTADO_PAGADA,
    ];

    // =====================================================
    // LABELS
    // =====================================================

    private const ESTADOS_LABELS = [
        self::ESTADO_PENDIENTE => 'Pendiente',
        self::ESTADO_PAGADA => 'Pagada',
        self::ESTADO_VENCIDA => 'Vencida',
    ];

    // =====================================================
    // BADGES
    // =====================================================

    private const ESTADO_BADGES = [
        self::ESTADO_PENDIENTE => 'badge-warning text-dark',
        self::ESTADO_PAGADA => 'badge-success',
        self::ESTADO_VENCIDA => 'badge-danger',
    ];

    // =====================================================
    // ID
    // =====================================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    // =====================================================
    // DATOS PRINCIPALES
    // =====================================================

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $numeroBoleta;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $expediente = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $profesional = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $emailProfesional = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $fechaVencimiento = null;

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $estado = self::ESTADO_PENDIENTE;

    // =====================================================
// ARCHIVOS
// =====================================================

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $archivoOriginalNombre = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $archivoOriginalNombreOriginal = null;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    private ?string $archivoOriginalMimeType = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $comprobanteNombre = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $comprobanteNombreOriginal = null;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    private ?string $comprobanteMimeType = null;

// =====================================================
// OBSERVACIONES
// =====================================================

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observaciones = null;

// =====================================================
// FECHAS
// =====================================================

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $fechaCreacion;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $fechaActualizacion;

// =====================================================
// LIFECYCLE CALLBACKS
// =====================================================

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $ahora = new \DateTimeImmutable();

        $this->fechaCreacion = $ahora;
        $this->fechaActualizacion = $ahora;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->fechaActualizacion = new \DateTimeImmutable();
    }

// =====================================================
// GETTERS & SETTERS
// =====================================================

    public function getArchivoOriginalNombre(): ?string
    {
        return $this->archivoOriginalNombre;
    }

    public function setArchivoOriginalNombre(?string $archivoOriginalNombre): static
    {
        $this->archivoOriginalNombre = $archivoOriginalNombre;

        return $this;
    }

    public function getArchivoOriginalNombreOriginal(): ?string
    {
        return $this->archivoOriginalNombreOriginal;
    }

    public function setArchivoOriginalNombreOriginal(?string $archivoOriginalNombreOriginal): static
    {
        $this->archivoOriginalNombreOriginal = $archivoOriginalNombreOriginal;

        return $this;
    }

    public function getArchivoOriginalMimeType(): ?string
    {
        return $this->archivoOriginalMimeType;
    }

    public function setArchivoOriginalMimeType(?string $archivoOriginalMimeType): static
    {
        $this->archivoOriginalMimeType = $archivoOriginalMimeType;

        return $this;
    }

    public function getComprobanteNombre(): ?string
    {
        return $this->comprobanteNombre;
    }

    public function setComprobanteNombre(?string $comprobanteNombre): static
    {
        $this->comprobanteNombre = $comprobanteNombre;

        return $this;
    }

    public function getComprobanteNombreOriginal(): ?string
    {
        return $this->comprobanteNombreOriginal;
    }

    public function setComprobanteNombreOriginal(?string $comprobanteNombreOriginal): static
    {
        $this->comprobanteNombreOriginal = $comprobanteNombreOriginal;

        return $this;
    }

    public function getComprobanteMimeType(): ?string
    {
        return $this->comprobanteMimeType;
    }

    public function setComprobanteMimeType(?string $comprobanteMimeType): static
    {
        $this->comprobanteMimeType = $comprobanteMimeType;

        return $this;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): static
    {
        $this->observaciones = $observaciones;

        return $this;
    }

    public function getFechaCreacion(): \DateTimeImmutable
    {
        return $this->fechaCreacion;
    }

    public function getFechaActualizacion(): \DateTimeImmutable
    {
        return $this->fechaActualizacion;
    }

// =====================================================
// HELPERS
// =====================================================

    public function hasArchivoOriginal(): bool
    {
        return !empty($this->archivoOriginalNombre);
    }

    public function hasComprobante(): bool
    {
        return !empty($this->comprobanteNombre);
    }

}