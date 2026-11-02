<?php

    namespace App\Entity;

    use App\Repository\CreatorRepository;
    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;
    use Doctrine\ORM\Mapping as ORM;
    use JMS\Serializer\Annotation\Groups;
    use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
    use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
    use Symfony\Component\Security\Core\User\UserInterface;
    use Symfony\Component\Validator\Constraints as Assert;
    use Hateoas\Configuration\Annotation as Hateoas;

    // Self-discoverability for GET {id}
    #[Hateoas\Relation(
        'self',
        href: new Hateoas\Route(
            'creator_details',
            parameters: ['id' => 'expr(object.getId())']
        ),
        exclusion: new Hateoas\Exclusion(
            groups: ['getCreator']
        )
    )]

    // Self-discoverability for PUT {id}
    #[Hateoas\Relation(
        'update',
        href: new Hateoas\Route(
            'update_creator',
            //parameters: ['id' => 'expr(object.getId())']
        ),
        exclusion: new Hateoas\Exclusion(
            groups: ['getCreator'],
            excludeIf: "expr(not is_granted('ROLE_CREATOR'))"
        )
    )]

    // Self-discoverability for DELETE {id}
    #[Hateoas\Relation(
        'delete',
        href: new Hateoas\Route(
            'delete_creator',
        //parameters: ['id' => 'expr(object.getId())']
        ),
        exclusion: new Hateoas\Exclusion(
            groups: ['getCreator'],
            excludeIf: "expr(not is_granted('ROLE_CREATOR'))"
        )
    )]


    #[ORM\Entity(repositoryClass: CreatorRepository::class)]
    #[UniqueEntity('email', message: 'Il semble que cet identifiant soit déjà utilisé')]
    class Creator implements UserInterface, PasswordAuthenticatedUserInterface
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        #[Groups(['getCreator', 'getGame'])]
        private ?int $id = null;

        #[ORM\Column(length: 180, unique: true)]
        #[Groups(['getCreator', 'getGame'])]
        #[Assert\NotBlank(message: 'Un email est requis pour la création du compte')]
        #[Assert\Email(message: 'Entrez un email au format correct')]
        private ?string $email = null;

        #[ORM\Column]
        private array $roles = [];

        #[ORM\Column]
        #[Assert\NotBlank(message: 'Un mot de passe est requis pour la création du compte')]
        private ?string $password = null;

        #[ORM\Column(length: 128)]
        #[Groups(['getCreator', 'getGame'])]
        #[Assert\NotBlank(message: 'Nom de famille manquant')]
        private ?string $lastName = null;

        #[ORM\Column(length: 255, nullable: true)]
        #[Groups(['getCreator', 'getGame'])]
        private ?string $firstName = null;

        #[ORM\ManyToMany(targetEntity: Game::class, mappedBy: 'creators')]
        #[Groups(['getCreator'])]
        private Collection $games;

        public function __construct()
        {
            $this->games = new ArrayCollection();
        }



        // Setters and Getters
        public function getId(): ?int
        {
            return $this->id;
        }

        public function getEmail(): ?string
        {
            return $this->email;
        }

        public function setEmail(string $email): static
        {
            $this->email = $email;

            return $this;
        }

        public function getUserIdentifier(): string
        {
            return (string) $this->email;
        }

        /**
         * Will be used by LexitJWT during authentication
         */
        public function getUsername(): string
        {
            return $this->getUserIdentifier();
        }

        public function getRoles(): array
        {
            $roles = $this->roles;
            // guarantee every user at least has ROLE_CREATOR
            $roles[] = 'ROLE_CREATOR';

            return array_unique($roles);
        }

        public function setRoles(array $roles): static
        {
            $this->roles = $roles;

            return $this;
        }

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

        public function getLastName(): ?string
        {
            return $this->lastName;
        }

        public function setLastName(string $lastName): static
        {
            $this->lastName = $lastName;

            return $this;
        }

        public function getFirstName(): ?string
        {
            return $this->firstName;
        }

        public function setFirstName(?string $firstName): static
        {
            $this->firstName = $firstName;

            return $this;
        }

        /**
         * @return Collection<int, Game>
         */
        public function getGames(): Collection
        {
            return $this->games;
        }

        public function addGame(Game $game): static
        {
            if (!$this->games->contains($game)) {
                $this->games->add($game);
                $game->addCreator($this);
            }

            return $this;
        }

        public function removeGame(Game $game): static
        {
            if ($this->games->removeElement($game)) {
                $game->removeCreator($this);
            }

            return $this;
        }
    }
