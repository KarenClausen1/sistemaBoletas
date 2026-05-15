<?php

namespace App\Security;

use App\Entity\Boleta;
use App\Entity\Usuario;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BoletaVoter extends Voter
{
    public const VIEW = 'BOLETA_VIEW';
    public const CREATE = 'BOLETA_CREATE';
    public const EDIT = 'BOLETA_EDIT';
    public const DELETE = 'BOLETA_DELETE';
    public const CHANGE_STATE = 'BOLETA_CHANGE_STATE';
    public const VIEW_ANY = 'BOLETA_VIEW_ANY';

    public function __construct(private readonly BoletaAccessHelper $accessHelper)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [self::CREATE, self::VIEW_ANY, self::CHANGE_STATE], true)) {
            return true;
        }

        return $subject instanceof Boleta && in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (! $user instanceof Usuario) {
            return false;
        }

        return match ($attribute) {
            self::CREATE, self::VIEW_ANY, self::VIEW => true,
            self::CHANGE_STATE => $this->accessHelper->canChangeBoletaState($user),
            self::EDIT => $subject instanceof Boleta && $this->accessHelper->canEditBoleta($user, $subject),
            self::DELETE => $subject instanceof Boleta && $this->accessHelper->canDeleteBoleta($user, $subject),
            default => false,
        };
    }
}

