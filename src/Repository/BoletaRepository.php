<?php

namespace App\Repository;

use App\Entity\Boleta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BoletaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boleta::class);
    }

    /**
     * Devuelve boletas filtradas por estado y/o búsqueda de texto.
     */
    public function findByFiltros(?string $estado, ?string $busqueda): array
    {
        $qb = $this->createQueryBuilder('b')
            ->orderBy('b.fechaCreacion', 'DESC');

        if ($estado && $estado !== 'todos') {
            $qb->andWhere('b.estado = :estado')
                ->setParameter('estado', $estado);
        }

        if ($busqueda) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('b.numeroBoleta', ':q'),
                    $qb->expr()->like('b.profesional', ':q'),
                    $qb->expr()->like('b.expediente', ':q'),
                    $qb->expr()->like('b.emailProfesional', ':q')
                )
            )->setParameter('q', '%' . $busqueda . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Devuelve conteos agrupados por estado.
     */
    public function getStats(): array
    {
        $rows = $this->createQueryBuilder('b')
            ->select('b.estado, COUNT(b.id) as total')
            ->groupBy('b.estado')
            ->getQuery()
            ->getResult();

        $stats = [
            'total' => 0,
            'pendiente' => 0,
            'pagada' => 0,
            'subida' => 0,
            'detenida' => 0,
        ];

        foreach ($rows as $row) {
            $stats[$row['estado']] = (int)$row['total'];
            $stats['total'] += (int)$row['total'];
        }

        return $stats;
    }
}