<?php

namespace App\Command;

use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:set-user-password', description: 'Set (hash) the password for a Usuario by dni or id')]
class SetUserPasswordCommand extends Command
{
    private UsuarioRepository $repo;
    private EntityManagerInterface $em;
    private ?LoggerInterface $logger;

    public function __construct(UsuarioRepository $repo, EntityManagerInterface $em, ?LoggerInterface $logger = null)
    {
        parent::__construct();
        $this->repo = $repo;
        $this->em = $em;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->addOption('dni', null, InputOption::VALUE_OPTIONAL, 'DNI of the user')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'ID of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'New plain password')
            ->setHelp(<<<'HELP'
Set (hash) the password for a Usuario.
Examples:
  php bin/console app:set-user-password --dni=12345678 'MyNewPass123!'
  php bin/console app:set-user-password --id=5 'MyNewPass123!'
HELP
);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dni = $input->getOption('dni');
        $id = $input->getOption('id');
        $password = (string)$input->getArgument('password');

        if (! $dni && ! $id) {
            $output->writeln('<error>Debe pasar --dni o --id</error>');
            return Command::INVALID;
        }

        $user = null;
        if ($dni) {
            $user = $this->repo->findByDni($dni);
            if (! $user) {
                $output->writeln(sprintf('<error>No se encontró usuario con dni %s</error>', $dni));
                return Command::FAILURE;
            }
        }

        if ($id) {
            $user = $this->repo->find($id);
            if (! $user) {
                $output->writeln(sprintf('<error>No se encontró usuario con id %s</error>', $id));
                return Command::FAILURE;
            }
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user->setPassword($hash);
        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('<info>Contraseña actualizada y hasheada correctamente.</info>');
        if ($this->logger) {
            $this->logger->info('SetUserPasswordCommand: password updated', ['user_id' => $user->getId()]);
        }

        return Command::SUCCESS;
    }
}
