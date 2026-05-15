<?php

namespace App\Security;

use App\Repository\UsuarioRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AuthAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    public const REGISTER_ROUTE = 'boleta_index';
    private $urlGenerator;
    private UsuarioRepository $usuarioRepository;

    public function __construct(UrlGeneratorInterface $urlGenerator, UsuarioRepository $usuarioRepository)
    {
        $this->urlGenerator = $urlGenerator;
        $this->usuarioRepository = $usuarioRepository;
    }

    public function authenticate(Request $request): Passport
    {
        $rawDni = trim((string) $request->request->get('dni', ''));
        $normalizedDni = preg_replace('/\D+/', '', $rawDni) ?: $rawDni;

        $request->getSession()->set(Security::LAST_USERNAME, $normalizedDni);

        $loader = function (string $userIdentifier) use ($normalizedDni, $rawDni) {
            foreach (array_values(array_unique(array_filter([$normalizedDni, $rawDni, $userIdentifier]))) as $identifier) {
                $user = $this->usuarioRepository->findByDni($identifier);
                if ($user) {
                    return $user;
                }

                $user = $this->usuarioRepository->findByUsername($identifier);
                if ($user) {
                    return $user;
                }
            }

            throw new UserNotFoundException(sprintf('Usuario "%s" no encontrado.', $userIdentifier));
        };

        return new Passport(
            new UserBadge($normalizedDni, $loader),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $redirect = $request->request->get('redirect', $request->query->get('redirect'));
        if (is_string($redirect) && $redirect !== '' && str_starts_with($redirect, '/')) {
            return new RedirectResponse($redirect);
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate(self::REGISTER_ROUTE));

    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
