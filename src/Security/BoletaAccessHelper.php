<?php

namespace App\Security;

use App\Entity\Boleta;
use App\Entity\Usuario;

class BoletaAccessHelper
{
    private const MANAGER_ROLES = [
        'ROLE_SUPER_ADMIN',
        'ROLE_SUPERADMIN',
        'ROLE_GESTOR_BOLETAS',
        'ROLE_ADMIN',
    ];

    public function canSeeAllBoletas(Usuario $usuario): bool
    {
        return true;
    }

    public function canManageBoletas(Usuario $usuario): bool
    {
        return $this->hasAnyRole($usuario, self::MANAGER_ROLES);
    }

    public function canManageUsers(Usuario $usuario): bool
    {
        return $this->hasAnyRole($usuario, ['ROLE_SUPER_ADMIN', 'ROLE_SUPERADMIN']);
    }

    public function canChangeBoletaState(Usuario $usuario): bool
    {
        return $this->canManageBoletas($usuario);
    }

    public function canUploadComprobante(Usuario $usuario): bool
    {
        return $this->canManageBoletas($usuario);
    }

    public function canEditBoleta(Usuario $usuario, Boleta $boleta): bool
    {
        if ($this->canManageBoletas($usuario)) {
            return true;
        }

        return $boleta->isPendiente() && ! $boleta->isCancelada() && $this->isOwnedByUser($boleta, $usuario);
    }

    public function canDeleteBoleta(Usuario $usuario, Boleta $boleta): bool
    {
        return $this->canManageBoletas($usuario);
    }

    public function canViewBoleta(Usuario $usuario, Boleta $boleta): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function getOwnershipIdentifiers(Usuario $usuario): array
    {
        $identifiers = [
            $usuario->getUserIdentifier(),
            $usuario->getNombreUsuario(),
            $usuario->getDni(),
        ];

        $normalized = [];
        foreach (array_filter($identifiers) as $value) {
            $normalized[] = $this->normalize((string) $value);
        }

        return array_values(array_unique($normalized));
    }

    public function isOwnedByUser(Boleta $boleta, Usuario $usuario): bool
    {
        $createdBy = $this->normalize((string) $boleta->getCreatedBy());

        if ($createdBy === '') {
            return false;
        }

        return in_array($createdBy, $this->getOwnershipIdentifiers($usuario), true);
    }

    /**
     * @param string[] $roles
     */
    private function hasAnyRole(Usuario $usuario, array $roles): bool
    {
        return (bool) array_intersect($usuario->getRoles(), $roles);
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}


