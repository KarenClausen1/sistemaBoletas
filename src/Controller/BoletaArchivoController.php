<?php

namespace App\Controller;

use App\Entity\Boleta;
use App\Security\BoletaVoter;
use App\Service\BoletaFileStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/boletas')]
final class BoletaArchivoController extends AbstractController
{
    public function __construct(private readonly BoletaFileStorage $fileStorage)
    {
    }

    #[Route('/{id}/archivo/original', name: 'boleta_archivo_original', methods: ['GET'])]
    public function original(Boleta $boleta): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted(BoletaVoter::VIEW, $boleta);

        $path = $this->fileStorage->getOriginalPath($boleta->getArchivoOriginalNombre());
        if (! $path || ! is_file($path)) {
            throw $this->createNotFoundException('El archivo original no está disponible.');
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $boleta->getArchivoOriginalNombreOriginal() ?: basename($path)
        );
        $response->headers->set('Content-Type', $boleta->getArchivoOriginalMimeType() ?: 'application/octet-stream');

        return $response;
    }

    #[Route('/{id}/archivo/comprobante', name: 'boleta_archivo_comprobante', methods: ['GET'])]
    public function comprobante(Boleta $boleta): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted(BoletaVoter::VIEW, $boleta);

        $path = $this->fileStorage->getComprobantePath($boleta->getComprobanteNombre());
        if (! $path || ! is_file($path)) {
            throw $this->createNotFoundException('El comprobante no está disponible.');
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $boleta->getComprobanteNombreOriginal() ?: basename($path)
        );
        $response->headers->set('Content-Type', $boleta->getComprobanteMimeType() ?: 'application/octet-stream');

        return $response;
    }
}

