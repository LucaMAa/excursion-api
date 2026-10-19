<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\AdminUserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an administrator or promote an existing user.',
)]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Administrator email address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = strtolower(trim((string) $input->getArgument('email')));

        if (false === filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 180) {
            $io->error('Enter a valid email address of at most 180 characters.');

            return Command::INVALID;
        }

        $question = new Question('Password (at least 12 characters): ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password = $io->askQuestion($question);

        if (!is_string($password) || strlen($password) < 12 || strlen($password) > 4096) {
            $io->error('The password must contain between 12 and 4096 characters.');

            return Command::INVALID;
        }

        $this->adminUserService->createOrPromote($email, $password);
        $io->success(sprintf('Administrator account "%s" is ready.', $email));

        return Command::SUCCESS;
    }
}
