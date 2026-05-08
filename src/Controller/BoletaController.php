<?php

namespace App\Controller;

use App\Entity\Boleta;
use App\Form\BoletaType;
use App\Repository\BoletaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[Route('/boletas')]
class BoletaController extends AbstractController
{
    // ── Listado principal ─────────────────────────────────────────────────────

    #[Route('', name: 'boleta_index', methods: ['GET'])]
    public function index(Request $request, BoletaRepository $repo): Response
    {
        // Requerir autenticación por sesión
        $sessionUser = $request->getSession()->get('usuario');
        if (empty($sessionUser)) {
            // redirigir al login e indicar a dónde volver
            return $this->redirectToRoute('app_login', ['redirect' => $request->getRequestUri()]);
        }

        $estado   = $request->query->get('estado', 'todos');
        $busqueda = $request->query->get('q', '');

        $boletas = $repo->findByFiltros($estado, $busqueda);
        $stats   = $repo->getStats();

        return $this->render('boleta/index.html.twig', [
            'boletas'  => $boletas,
            'stats'    => $stats,
            'estado'   => $estado,
            'busqueda' => $busqueda,
            'estados'  => Boleta::ESTADOS,
        ]);
    }

    // ── Nueva boleta ──────────────────────────────────────────────────────────

    #[Route('/nueva', name: 'boleta_nueva', methods: ['GET', 'POST'])]
    public function nueva(Request $request, EntityManagerInterface $em): Response
    {
        // Requerir autenticación por sesión
        $sessionUser = $request->getSession()->get('usuario');
        if (empty($sessionUser)) {
            return $this->redirectToRoute('app_login', ['redirect' => $request->getRequestUri()]);
        }

        $boleta = new Boleta();
        $form   = $this->createForm(BoletaType::class, $boleta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Asignar createdBy al usuario actual (si hay uno en sesión)
                if (!empty($sessionUser) && !empty($sessionUser['username'])) {
                    $boleta->setCreatedBy($sessionUser['username']);
                }

                $em->persist($boleta);
                $em->flush();
                $this->addFlash('success', 'Boleta creada correctamente.');
                return $this->redirectToRoute('boleta_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Ya existe una boleta con ese número.');
            }
        }

        return $this->render('boleta/form.html.twig', [
            'form'   => $form->createView(),
            'titulo' => 'Nueva boleta',
            'boleta' => null,
        ]);
    }

    // ── Ver / Editar boleta ───────────────────────────────────────────────────

    #[Route('/{id}/editar', name: 'boleta_editar', methods: ['GET', 'POST'])]
    public function editar(Request $request, Boleta $boleta, EntityManagerInterface $em): Response
    {
        // Sólo el creador o administradores en sesión pueden editar
        $sessionUser = $request->getSession()->get('usuario');
        $username = $sessionUser['username'] ?? null;
        $roles = $sessionUser['roles'] ?? [];
        $isAdmin = in_array('ROLE_ADMIN', $roles, true);

        if (! $username || ($boleta->getCreatedBy() !== $username && ! $isAdmin)) {
            throw new AccessDeniedHttpException('No estás autorizado para editar esta boleta.');
        }

        $form = $this->createForm(BoletaType::class, $boleta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', 'Boleta actualizada correctamente.');
                return $this->redirectToRoute('boleta_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error al guardar: ' . $e->getMessage());
            }
        }

        return $this->render('boleta/form.html.twig', [
            'form'   => $form->createView(),
            'titulo' => 'Editar boleta',
            'boleta' => $boleta,
        ]);
    }

    // ── Cambio rápido de estado (desde la tabla) ──────────────────────────────

    #[Route('/{id}/estado', name: 'boleta_estado', methods: ['POST'])]
    public function cambiarEstado(Request $request, Boleta $boleta, EntityManagerInterface $em): Response
    {
        // Sólo administradores (sesión) pueden cambiar el estado
        $sessionUser = $request->getSession()->get('usuario');
        $username = $sessionUser['username'] ?? null;
        $roles = $sessionUser['roles'] ?? [];
        $isAdmin = in_array('ROLE_ADMIN', $roles, true);

        if (! $isAdmin) {
            throw new AccessDeniedHttpException('No estás autorizado para cambiar el estado de esta boleta.');
        }

        $nuevoEstado = $request->request->get('estado');

        if (array_key_exists($nuevoEstado, array_flip(Boleta::ESTADOS))) {
            $boleta->setEstado($nuevoEstado);
            $em->flush();
            $this->addFlash('success', 'Estado actualizado.');
        }

        return $this->redirectToRoute('boleta_index', [
            'estado' => $request->request->get('filtro_estado', 'todos'),
        ]);
    }

    // ── Eliminar boleta ───────────────────────────────────────────────────────

    #[Route('/{id}/eliminar', name: 'boleta_eliminar', methods: ['POST'])]
    public function eliminar(Request $request, Boleta $boleta, EntityManagerInterface $em): Response
    {
        // Sólo el creador o administradores pueden eliminar (sesión)
        $sessionUser = $request->getSession()->get('usuario');
        $username = $sessionUser['username'] ?? null;
        $roles = $sessionUser['roles'] ?? [];
        $isAdmin = in_array('ROLE_ADMIN', $roles, true);

        if (! $username || ($boleta->getCreatedBy() !== $username && ! $isAdmin)) {
            throw new AccessDeniedHttpException('No estás autorizado para eliminar esta boleta.');
        }

        $em->remove($boleta);
        $em->flush();
        $this->addFlash('success', 'Boleta eliminada exitosamente.');
        return $this->redirectToRoute('boleta_index');
    }
}

