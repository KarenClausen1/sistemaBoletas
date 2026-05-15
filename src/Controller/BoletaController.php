<?php
namespace App\Controller;
use App\Entity\Boleta;
use App\Entity\Usuario;
use App\Form\BoletaType;
use App\Repository\BoletaRepository;
use App\Security\BoletaAccessHelper;
use App\Security\BoletaVoter;
use App\Service\BoletaFileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/boletas')]
class BoletaController extends AbstractController
{
    public function __construct(private readonly BoletaFileStorage $fileStorage)
    {
    }
    #[Route('', name: 'boleta_index', methods: ['GET'])]
    public function index(Request $request, BoletaRepository $repo, BoletaAccessHelper $accessHelper): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        if (! $usuario) {
            return $this->redirectToRoute('app_login', ['redirect' => $request->getRequestUri()]);
        }
        $estado = (string) $request->query->get('estado', 'todos');
        $busqueda = (string) $request->query->get('q', '');
        return $this->render('boleta/index.html.twig', [
            'boletas' => $repo->findByFiltros($estado, $busqueda),
            'stats' => $repo->getStats(),
            'estado' => $estado,
            'busqueda' => $busqueda,
            'estados' => Boleta::ESTADOS,
            'can_manage_boletas' => $accessHelper->canManageBoletas($usuario),
            'can_manage_users' => $accessHelper->canManageUsers($usuario),
            'pendientes_urgentes' => $repo->getPendientesUrgentes(),
            'pagadas_sin_comprobante' => $repo->getPagadasSinComprobante(),
            'finalizadas' => $repo->getFinalizadas(),
        ]);
    }
    #[Route('/nueva', name: 'boleta_nueva', methods: ['GET', 'POST'])]
    public function nueva(Request $request, EntityManagerInterface $em, BoletaAccessHelper $accessHelper): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        if (! $usuario) {
            return $this->redirectToRoute('app_login', ['redirect' => $request->getRequestUri()]);
        }
        $this->denyAccessUnlessGranted(BoletaVoter::CREATE);
        $boleta = new Boleta();
        $form = $this->createForm(BoletaType::class, $boleta, [
            'require_original_file' => true,
            'show_comprobante_upload' => $accessHelper->canUploadComprobante($usuario),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $storedFiles = ['new' => [], 'old' => []];
            try {
                $boleta->setCreatedBy($usuario->getNombreUsuario());
                $boleta->setUpdatedBy($usuario->getNombreUsuario());
                $boleta->setFechaCambioEstado(new \DateTimeImmutable());
                $boleta->setUsuarioCambioEstado($usuario->getNombreUsuario());
                $storedFiles = $this->handleUploadedFiles($boleta, $form);
                $em->persist($boleta);
                $em->flush();
                $this->cleanupStoredFiles($storedFiles['old']);
                $this->addFlash('success', 'Boleta creada correctamente.');
                return $this->redirectToRoute('boleta_index');
            } catch (\Throwable $e) {
                $this->cleanupStoredFiles($storedFiles['new']);
                $this->addFlash('error', 'No se pudo crear la boleta.');
            }
        }
        return $this->render('boleta/form.html.twig', [
            'form' => $form->createView(),
            'titulo' => 'Nueva boleta',
            'boleta' => null,
            'can_manage_boletas' => $accessHelper->canManageBoletas($usuario),
        ]);
    }
    #[Route('/{id}/editar', name: 'boleta_editar', methods: ['GET', 'POST'])]
    public function editar(Request $request, Boleta $boleta, EntityManagerInterface $em, BoletaAccessHelper $accessHelper): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        if (! $usuario) {
            return $this->redirectToRoute('app_login', ['redirect' => $request->getRequestUri()]);
        }
        $this->denyAccessUnlessGranted(BoletaVoter::EDIT, $boleta);
        $form = $this->createForm(BoletaType::class, $boleta, [
            'require_original_file' => false,
            'show_comprobante_upload' => $accessHelper->canUploadComprobante($usuario),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $storedFiles = ['new' => [], 'old' => []];
            try {
                $boleta->setUpdatedBy($usuario->getNombreUsuario());
                $storedFiles = $this->handleUploadedFiles($boleta, $form);
                if ($form->has('comprobantePago') && $form->get('comprobantePago')->getData() instanceof UploadedFile) {
                    $boleta->setEstado(Boleta::ESTADO_PAGADA);
                    $boleta->setFechaCambioEstado(new \DateTimeImmutable());
                    $boleta->setUsuarioCambioEstado($usuario->getNombreUsuario());
                }
                $em->flush();
                $this->cleanupStoredFiles($storedFiles['old']);
                $this->addFlash('success', 'Boleta actualizada correctamente.');
                return $this->redirectToRoute('boleta_index');
            } catch (\Throwable $e) {
                $this->cleanupStoredFiles($storedFiles['new']);
                $this->addFlash('error', 'Error al guardar: ' . $e->getMessage());
            }
        }
        return $this->render('boleta/form.html.twig', [
            'form' => $form->createView(),
            'titulo' => 'Editar boleta',
            'boleta' => $boleta,
            'can_manage_boletas' => $accessHelper->canManageBoletas($usuario),
        ]);
    }
    #[Route('/{id}/estado', name: 'boleta_estado', methods: ['POST'])]
    public function cambiarEstado(Request $request, Boleta $boleta, EntityManagerInterface $em): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        if (! $usuario) {
            return $this->redirectToRoute('app_login', ['redirect' => $request->getRequestUri()]);
        }
        $this->denyAccessUnlessGranted(BoletaVoter::CHANGE_STATE, $boleta);

        if (! $this->isCsrfTokenValid('boleta_estado_' . $boleta->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF inválido.');

            return $this->redirectToRoute('boleta_index');
        }

        $nuevoEstado = Boleta::normalizeEstado((string) $request->request->get('estado', ''));
        if (in_array($nuevoEstado, array_values(Boleta::ESTADOS), true)) {
            $boleta->setEstado($nuevoEstado);
            $boleta->setFechaCambioEstado(new \DateTimeImmutable());
            $boleta->setUsuarioCambioEstado($usuario->getNombreUsuario());
            $boleta->setUpdatedBy($usuario->getNombreUsuario());
            $em->flush();
            $this->addFlash('success', 'Estado actualizado.');
        } else {
            $this->addFlash('error', 'Estado inválido.');
        }
        return $this->redirectToRoute('boleta_index', [
            'estado' => $request->request->get('filtro_estado', 'todos'),
        ]);
    }
    #[Route('/{id}/eliminar', name: 'boleta_eliminar', methods: ['POST'])]
    public function eliminar(Request $request, Boleta $boleta, EntityManagerInterface $em): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        if (! $usuario) {
            return $this->redirectToRoute('app_login', ['redirect' => $request->getRequestUri()]);
        }
        $this->denyAccessUnlessGranted(BoletaVoter::DELETE, $boleta);

        if (! $this->isCsrfTokenValid('boleta_cancelar_' . $boleta->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF inválido.');

            return $this->redirectToRoute('boleta_index');
        }

        $boleta->setEstado(Boleta::ESTADO_CANCELADA);
        $boleta->setFechaCambioEstado(new \DateTimeImmutable());
        $boleta->setUsuarioCambioEstado($usuario->getNombreUsuario());
        $boleta->setUpdatedBy($usuario->getNombreUsuario());
        $em->flush();
        $this->addFlash('success', 'Boleta cancelada correctamente.');
        return $this->redirectToRoute('boleta_index');
    }
    /**
     * @return array{new: array<int, array{type: string, filename: string}>, old: array<int, array{type: string, filename: string}>}
     */
    private function handleUploadedFiles(Boleta $boleta, FormInterface $form): array
    {
        $storedFiles = ['new' => [], 'old' => []];
        $usuario = $this->getAuthenticatedUsuario();
        try {
            if ($form->has('archivoOriginal')) {
                $archivoOriginal = $form->get('archivoOriginal')->getData();
                if ($archivoOriginal instanceof UploadedFile) {
                    $oldFile = $boleta->getArchivoOriginalNombre();
                    $storedName = $this->fileStorage->storeOriginal($archivoOriginal);
                    $boleta
                        ->setArchivoOriginalNombre($storedName)
                        ->setArchivoOriginalNombreOriginal($archivoOriginal->getClientOriginalName())
                        ->setArchivoOriginalMimeType($archivoOriginal->getMimeType())
                        ->setUpdatedBy($usuario?->getNombreUsuario());
                    if ($oldFile) {
                        $storedFiles['old'][] = ['type' => 'original', 'filename' => $oldFile];
                    }
                    $storedFiles['new'][] = ['type' => 'original', 'filename' => $storedName];
                }
            }
            if ($form->has('comprobantePago')) {
                $comprobante = $form->get('comprobantePago')->getData();
                if ($comprobante instanceof UploadedFile) {
                    $oldFile = $boleta->getComprobanteNombre();
                    $storedName = $this->fileStorage->storeComprobante($comprobante);
                    $boleta
                        ->setComprobanteNombre($storedName)
                        ->setComprobanteNombreOriginal($comprobante->getClientOriginalName())
                        ->setComprobanteMimeType($comprobante->getMimeType())
                        ->setEstado(Boleta::ESTADO_PAGADA)
                        ->setFechaCambioEstado(new \DateTimeImmutable())
                        ->setUsuarioCambioEstado($usuario?->getNombreUsuario())
                        ->setUpdatedBy($usuario?->getNombreUsuario());
                    if ($oldFile) {
                        $storedFiles['old'][] = ['type' => 'comprobante', 'filename' => $oldFile];
                    }
                    $storedFiles['new'][] = ['type' => 'comprobante', 'filename' => $storedName];
                }
            }
        } catch (\Throwable $e) {
            $this->cleanupStoredFiles($storedFiles['new']);
            throw $e;
        }
        return $storedFiles;
    }
    /**
     * @param array<int, array{type: string, filename: string}> $storedFiles
     */
    private function cleanupStoredFiles(array $storedFiles): void
    {
        foreach ($storedFiles as $storedFile) {
            if ($storedFile['type'] === 'original') {
                $this->fileStorage->deleteOriginal($storedFile['filename']);
                continue;
            }
            $this->fileStorage->deleteComprobante($storedFile['filename']);
        }
    }
    private function getAuthenticatedUsuario(): ?Usuario
    {
        $user = $this->getUser();
        return $user instanceof Usuario ? $user : null;
    }
}
