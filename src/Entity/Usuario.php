<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UsuarioRepository;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\Table(name: 'usuarios')]
class Usuario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $nombreUsuario;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $dni = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $codigoInterno = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password;

    public function getId(): ?int { return $this->id; }
    public function getNombreUsuario(): string { return $this->nombreUsuario; }
    public function setNombreUsuario(string $v): static { $this->nombreUsuario = $v; return $this; }

    public function getDni(): ?string { return $this->dni; }
    public function setDni(?string $v): static { $this->dni = $v; return $this; }

    public function getCodigoInterno(): ?string { return $this->codigoInterno; }
    public function setCodigoInterno(?string $v): static { $this->codigoInterno = $v; return $this; }

    public function getRoles(): array { return $this->roles; }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }
}
