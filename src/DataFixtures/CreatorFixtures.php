<?php

    namespace App\DataFixtures;

    use App\Entity\Creator;
    use Doctrine\Bundle\FixturesBundle\Fixture;
    use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
    use Doctrine\Persistence\ObjectManager;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

    class CreatorFixtures extends Fixture implements FixtureGroupInterface
    {
        public function __construct(
            private readonly UserPasswordHasherInterface $passwordHasher
        ){}


        public function load(ObjectManager $manager): void
        {
            for ($i=0; $i<=10; $i++) {
                $creator = new Creator();
                $creator->setEmail('creator-'.$i.'@api.com');
                $creator->setPassword($this->passwordHasher->hashPassword($creator, 'password'));
                $creator->setLastName('Ouattara-'.$i);

                $manager->persist($creator);
            }

            $manager->flush();
        }


        public static function getGroups(): array
        {
            return ['creator_fixtures'];
        }
    }
