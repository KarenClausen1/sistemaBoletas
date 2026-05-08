<?php

namespace App\Controller;

use App\Repository\UsuarioRepository;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AuthController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request, UsuarioRepository $repo, EntityManagerInterface $entityManager, ?CsrfTokenManagerInterface $csrfManager = null, ?LoggerInterface $logger = null): Response
    {
        if ($request->getSession()->get('usuario')) {
            return $this->redirectToRoute('boleta_index');
        }

        $error = null;
        $lastDni = '';
        $redirect = $request->query->get('redirect', $request->request->get('redirect', null));

        if ($request->isMethod('POST')) {
            $rawInput = (string)$request->request->get('dni');
            $lastDni = trim($rawInput);
            $password = (string)$request->request->get('password');
            $submittedToken = $request->request->get('_csrf_token');

            if ($csrfManager) {
                $token = new CsrfToken('authenticate', $submittedToken);
                if (! $csrfManager->isTokenValid($token)) {
                    $error = 'Token CSRF inválido.';
                    if ($logger) $logger->warning('CSRF token inválido en login', ['dni' => $lastDni]);
                }
            }

            if (! $error) {
                // normalizamos el DNI quitando todo lo que no sea dígito
                $dniOnly = preg_replace('/\D/', '', $lastDni);

                $user = null;
                if ($dniOnly !== '') {
                    $user = $repo->findByDni($dniOnly);
                    if ($logger) $logger->info('Búsqueda de usuario por DNI normalizado', ['input' => $lastDni, 'dniOnly' => $dniOnly, 'found' => (bool)$user]);
                }

                // si no encontramos por dni normalizado, intentamos con el valor original
                if (! $user && $lastDni !== '') {
                    $user = $repo->findByDni($lastDni);
                    if ($logger) $logger->info('Búsqueda de usuario por DNI raw', ['input' => $lastDni, 'found' => (bool)$user]);
                }

                // fallback: buscar por nombre de usuario
                if (! $user && $lastDni !== '') {
                    $user = $repo->findByUsername($lastDni);
                    if ($logger) $logger->info('Búsqueda fallback por nombreUsuario', ['input' => $lastDni, 'found' => (bool)$user]);
                }

                if (! $user) {
                    if ($logger) $logger->info('Intento de login con usuario no encontrado', ['input' => $lastDni]);
                } else {
                    $stored = $user->getPassword();
                    $storedTrim = $stored !== null ? trim($stored, "\"\' \t\n\r\0\x0B") : '';
                    $passwordTrim = trim($password);

                    // Si el password almacenado es un hash válido, usamos password_verify
                    if ($storedTrim !== '' && password_verify($passwordTrim, $storedTrim)) {
                        // login
                        $request->getSession()->set('usuario', [
                            'id' => $user->getId(),
                            'username' => $user->getNombreUsuario(),
                            'roles' => $user->getRoles(),
                        ]);

                        if ($logger) $logger->info('Login exitoso', ['input' => $lastDni, 'user_id' => $user->getId()]);

                        if ($redirect) {
                            return $this->redirect($redirect);
                        }

                        return $this->redirectToRoute('boleta_index');
                    }

                    // Si no coincide el hash, verificamos si la contraseña estaba almacenada en texto
                    if ($storedTrim === $passwordTrim) {
                        // Migramos a hash seguro y guardamos
                        $newHash = password_hash($passwordTrim, PASSWORD_DEFAULT);
                        $user->setPassword($newHash);
                        $entityManager->persist($user);
                        $entityManager->flush();

                        if ($logger) $logger->warning('Se migró contraseña en texto plano a hash para usuario', ['input' => $lastDni, 'user_id' => $user->getId()]);

                        // login
                        $request->getSession()->set('usuario', [
                            'id' => $user->getId(),
                            'username' => $user->getNombreUsuario(),
                            'roles' => $user->getRoles(),
                        ]);

                        if ($redirect) {
                            return $this->redirect($redirect);
                        }

                        return $this->redirectToRoute('boleta_index');
                    }

                    if ($logger) $logger->info('Falló verificación de contraseña', ['input' => $lastDni, 'user_id' => $user->getId()]);
                }

                $error = 'Usuario o contraseña incorrectos.';
            }
        }

        return $this->render('security/login.html.twig', ['last_username' => $lastDni, 'error' => $error, 'redirect' => $redirect]);
    }

    // Ruta de depuración — solo muestra datos si la app está en modo debug (parámetro kernel.debug=true)
    #[Route(path: '/_debug/user/{dni}', name: 'app_debug_user', methods: ['GET'])]
    public function debugUser(string $dni, UsuarioRepository $repo, Request $request): Response
    {
        // solo permitir en modo debug
        if (! $this->getParameter('kernel.debug')) {
            return new JsonResponse(['error' => 'Not available'], Response::HTTP_FORBIDDEN);
        }

        $p = $request->query->get('p'); // contraseña a testear opcional

        $dniOnly = preg_replace('/\D/', '', $dni);
        $user = null;
        if ($dniOnly !== '') {
            $user = $repo->findByDni($dniOnly);
        }
        if (! $user) {
            $user = $repo->findByDni($dni);
        }
        if (! $user) {
            $user = $repo->findByUsername($dni);
        }

        if (! $user) {
            return new JsonResponse(['found' => false]);
        }

        $stored = $user->getPassword();
        $storedTrim = $stored !== null ? trim($stored, "\"\' \t\n\r\0\x0B") : '';

        $out = [
            'found' => true,
            'id' => $user->getId(),
            'nombreUsuario' => $user->getNombreUsuario(),
            'dni' => $user->getDni(),
            'roles' => $user->getRoles(),
            'password_raw' => $stored,
            'password_trim' => $storedTrim,
        ];

        if ($p !== null) {
            $pTrim = trim((string)$p);
            $out['password_test'] = $pTrim;
            $out['password_verify'] = $storedTrim !== '' ? password_verify($pTrim, $storedTrim) : null;
            $out['plaintext_equal'] = $storedTrim === $pTrim;
        }

        return new JsonResponse($out);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->remove('usuario');
        return $this->redirectToRoute('app_login');
    }
}
