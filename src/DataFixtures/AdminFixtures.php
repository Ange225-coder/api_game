<?php

    namespace App\DataFixtures;

    use App\Entity\Admin;
    use Doctrine\Bundle\FixturesBundle\Fixture;
    use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
    use Doctrine\Persistence\ObjectManager;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

    class AdminFixtures extends Fixture implements FixtureGroupInterface
    {
        public function __construct(
            private readonly UserPasswordHasherInterface $passwordHasher
        ){}



        public static function getGroups(): array
        {
            return ['admin_fixtures'];
        }

        public function load(ObjectManager $manager): void
        {
            $adminEntity = new Admin();
            $adminEntity->setAdminName('admin');
            $adminEntity->setPassword($this->passwordHasher->hashPassword($adminEntity, 'admin-password'));

            $manager->persist($adminEntity);
            $manager->flush();
        }
    }
