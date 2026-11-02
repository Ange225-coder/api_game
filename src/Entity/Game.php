<?php

    namespace App\Entity;

    use App\Repository\GameRepository;
    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;
    use Doctrine\ORM\Mapping as ORM;
    use JMS\Serializer\Annotation\Groups;
    use JMS\Serializer\Annotation\Since;
    use Symfony\Component\Validator\Constraints as Assert;
    use Hateoas\Configuration\Annotation as Hateoas;

    // self discoverability for {{ GET }}
    #[Hateoas\Relation(
        'self',
        href: new Hateoas\Route(
            'game_details',
            parameters: ['id' => 'expr(object.getId())']
        ),
        exclusion: new Hateoas\Exclusion(
            groups: ['getGame']
        )
    )]

    // self discoverability for {{ UPDATE }}
    #[Hateoas\Relation(
        'update',
        href: new Hateoas\Route(
            'update_game',
            parameters: ['id' => 'expr(object.getId())']
        ),
        exclusion: new Hateoas\Exclusion(
            groups: ['getGame'],
            excludeIf: "expr(not is_granted('ROLE_CREATOR'))"
        )
    )]

    // self discoverability for {{ DELETE }}
    #[Hateoas\Relation(
        'delete',
        href: new Hateoas\Route(
            'delete_game',
            parameters: ['id' => 'expr(object.getId())']
        ),
        exclusion: new Hateoas\Exclusion(
            groups: ['getGame'],
            excludeIf: "expr(not is_granted('ROLE_CREATOR'))"
        )
    )]


    #[ORM\Entity(repositoryClass: GameRepository::class)]
    class Game
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        #[Groups(['getGame', 'getCreator'])]
        private ?int $id = null;

        #[ORM\Column(length: 128)]
        #[Groups(['getGame', 'getCreator'])]
        #[Assert\NotBlank(message: 'Ajouter le nom du jeux')]
        #[Assert\Length(
            min: 3,
            max: 55,
            minMessage: 'Le caractère minimum requis pour le nom du jeu est {{ limit }}',
            maxMessage: 'Le caractère maximum requis pour le nom du jeu est {{ limit }}'
        )]
        private ?string $name = null;

        #[ORM\Column(length: 128, nullable: true)]
        #[Groups(['getGame', 'getCreator'])]
        #[Assert\Length(
            min: 3,
            max: 55,
            minMessage: 'Le caractère minimum requis pour le type est {{ limit }}',
            maxMessage: 'Le caractère maximum requis pour le type est {{ limit }}'
        )]
        private ?string $type = null;

        #[ORM\ManyToMany(targetEntity: Creator::class, inversedBy: 'games')]
        #[Groups(['getGame'])]
        private ?Collection $creators = null;

        #[ORM\Column]
        #[Since('2.0')]
        #[Groups(['getGame', 'getCreator'])]
        #[Assert\NotBlank(message: 'Entrer le nombre de joueur possible pour le jeu')]
        #[Assert\Positive(message: 'Entrer uniquement des nombres positifs')]
        private ?int $numberOfPlayers = null;

        public function __construct()
        {
            $this->creators = new ArrayCollection();
        }



        // Setters and Getters
        public function getId(): ?int
        {
            return $this->id;
        }

        public function getName(): ?string
        {
            return $this->name;
        }

        public function setName(string $name): static
        {
            $this->name = $name;

            return $this;
        }

        public function getType(): ?string
        {
            return $this->type;
        }

        public function setType(?string $type): static
        {
            $this->type = $type;

            return $this;
        }

        // a été modifié
        public function getCreators(): Collection
        {
            if ($this->creators === null) {
                $this->creators = new ArrayCollection();
            }

            return $this->creators;
        }

        // a été modifié
        public function addCreator(Creator $creator): static
        {
            $creatorsCollection = $this->getCreators();

            if (!$creatorsCollection->contains($creator)) {
                $creatorsCollection->add($creator);
            }

            return $this;
        }

        public function removeCreator(Creator $creator): static
        {
            $this->creators->removeElement($creator);

            return $this;
        }

        public function getNumberOfPlayers(): ?int
        {
            return $this->numberOfPlayers;
        }

        public function setNumberOfPlayers(int $numberOfPlayers): static
        {
            $this->numberOfPlayers = $numberOfPlayers;

            return $this;
        }
    }
