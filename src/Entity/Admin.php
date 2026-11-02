<?php

    namespace App\Entity;

    use App\Repository\AdminRepository;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
    use Symfony\Component\Security\Core\User\UserInterface;

    #[ORM\Entity(repositoryClass: AdminRepository::class)]
    #[ORM\Table(name: '`admin`')]
    #[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_ADMIN_NAME', fields: ['adminName'])]
    class Admin implements UserInterface, PasswordAuthenticatedUserInterface
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null;

        #[ORM\Column(length: 180)]
        private ?string $adminName = null;

        /**
         * @var list<string> The user roles
         */
        #[ORM\Column]
        private array $roles = [];

        /**
         * @var string The hashed password
         */
        #[ORM\Column]
        private ?string $password = null;



        // Setters and Getters
        public function getId(): ?int
        {
            return $this->id;
        }

        public function getAdminName(): ?string
        {
            return $this->adminName;
        }

        public function setAdminName(string $adminName): static
        {
            $this->adminName = $adminName;

            return $this;
        }

        /**
         * A visual identifier that represents this user.
         *
         * @see UserInterface
         */
        public function getUserIdentifier(): string
        {
            return (string) $this->adminName;
        }

        /**
         * Will be used by LexitJWT for api authentication
         */
        public function username(): string
        {
            return $this->getUserIdentifier();
        }


        /**
         * @see UserInterface
         */
        public function getRoles(): array
        {
            $roles = $this->roles;
            // guarantee every user at least has ROLE_ADMIN
            $roles[] = 'ROLE_ADMIN';

            return array_unique($roles);
        }

        /**
         * @param list<string> $roles
         */
        public function setRoles(array $roles): static
        {
            $this->roles = $roles;

            return $this;
        }

        /**
         * @see PasswordAuthenticatedUserInterface
         */
        public function getPassword(): ?string
        {
            return $this->password;
        }

        public function setPassword(string $password): static
        {
            $this->password = $password;

            return $this;
        }

        /**
         * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
         */
        public function __serialize(): array
        {
            $data = (array) $this;
            $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

            return $data;
        }
    }
