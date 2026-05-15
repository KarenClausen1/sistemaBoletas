<?php

namespace App\Repository;

use App\Entity\Boleta;
use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class BoletaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boleta::class);
    }

    /**
     * Devuelve boletas filtradas por estado, búsqueda de texto y visibilidad por usuario.
     */
    public function findByFiltros(?string $estado, ?string $busqueda, ?Usuario $usuario = null): array
    {
        $qb = $this->createBaseQueryBuilder();

        if ($estado && $estado !== 'todos') {
            $qb->andWhere('b.estado IN (:estados)')
                ->setParameter('estados', $this->getEstadoFilterValues($estado));
        }

        if ($busqueda) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('b.numeroBoleta', ':q'),
                    $qb->expr()->like('b.profesional', ':q'),
                    $qb->expr()->like('b.expediente', ':q'),
                    $qb->expr()->like('b.emailProfesional', ':q'),
                    $qb->expr()->like('b.createdBy', ':q'),
                    $qb->expr()->like('b.archivoOriginalNombreOriginal', ':q'),
                    $qb->expr()->like('b.comprobanteNombreOriginal', ':q')
                )
            )->setParameter('q', '%' . $busqueda . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Devuelve conteos agrupados por estado.
     */
    public function getStats(?Usuario $usuario = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b.estado, COUNT(b.id) as total')
            ->groupBy('b.estado');

        $rows = $qb->getQuery()->getResult();

        $stats = [
            'total' => 0,
            'pendiente' => 0,
            'pagada' => 0,
            'vencida' => 0,
            'cancelada' => 0,
        ];

        foreach ($rows as $row) {
            $estado = Boleta::normalizeEstado((string) $row['estado']);

            if (! array_key_exists($estado, $stats)) {
                $stats[$estado] = 0;
            }

            $stats[$estado] += (int) $row['total'];
            $stats['total'] += (int)$row['total'];
        }

        return $stats;
    }

    /**
     * @return Boleta[]
     */
    public function getPendientesUrgentes(int $dias = 3): array
    {
        $hasta = new \DateTimeImmutable(sprintf('+%d days', max(0, $dias)));

        return $this->createBaseQueryBuilder('b')
            ->andWhere('b.estado IN (:estados)')
            ->andWhere('b.fechaVencimiento IS NOT NULL')
            ->andWhere('b.fechaVencimiento <= :hasta')
            ->setParameter('estados', $this->getEstadoFilterValues(Boleta::ESTADO_PENDIENTE))
            ->setParameter('hasta', $hasta)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Boleta[]
     */
    public function getPagadasSinComprobante(): array
    {
        return $this->createBaseQueryBuilder('b')
            ->andWhere('b.estado IN (:estados)')
            ->andWhere('b.comprobanteNombre IS NULL')
            ->setParameter('estados', $this->getEstadoFilterValues(Boleta::ESTADO_PAGADA))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Boleta[]
     */
    public function getFinalizadas(): array
    {
        return $this->createBaseQueryBuilder('b')
            ->andWhere('b.estado IN (:estados)')
            ->andWhere('b.comprobanteNombre IS NOT NULL')
            ->setParameter('estados', $this->getEstadoFilterValues(Boleta::ESTADO_PAGADA))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return string[]
     */
    private function getOwnershipIdentifiers(Usuario $usuario): array
    {
        $identifiers = array_filter([
            $usuario->getUserIdentifier(),
            $usuario->getNombreUsuario(),
            $usuario->getDni(),
        ]);

        $normalized = [];
        foreach ($identifiers as $identifier) {
            $normalized[] = mb_strtolower(trim((string) $identifier));
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return string[]
     */
    private function getEstadoFilterValues(string $estado): array
    {
        $estado = Boleta::normalizeEstado($estado);

        return match ($estado) {
            Boleta::ESTADO_PENDIENTE => [Boleta::ESTADO_PENDIENTE, Boleta::ESTADO_DETENIDA, Boleta::ESTADO_EN_PROCESO],
            Boleta::ESTADO_PAGADA => [Boleta::ESTADO_PAGADA, Boleta::ESTADO_SUBIDA, Boleta::ESTADO_COMPROBANTE_SUBIDO, Boleta::ESTADO_FINALIZADA],
            Boleta::ESTADO_VENCIDA => [Boleta::ESTADO_VENCIDA],
            Boleta::ESTADO_CANCELADA => [Boleta::ESTADO_CANCELADA],
            default => [$estado],
        };
    }

    private function createBaseQueryBuilder(string $alias = 'b'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->orderBy($alias . '.fechaCreacion', 'DESC');
    }
}