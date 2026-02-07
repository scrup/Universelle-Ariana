<?php
namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-users',
    description: 'Create test users',
)]
class SeedUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = [
            ['email' => 'admin@test.com', 'password' => 'admin123', 'roles' => ['ROLE_ADMIN']],
            ['email' => 'assoc@test.com', 'password' => 'assoc123', 'roles' => ['ROLE_ASSOC']],
            ['email' => 'donor@test.com', 'password' => 'donor123', 'roles' => ['ROLE_DONATEUR']],
        ];

        foreach ($users as $userData) {
            $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
            if ($existing) {
                $output->writeln("User {$userData['email']} already exists");
                continue;
            }

            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $hashed = $this->hasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashed);

            $this->em->persist($user);
            $output->writeln("Created {$userData['email']}");
        }

        $this->em->flush();
        $output->writeln('<info>Done!</info>');
        return Command::SUCCESS;
    }
}
