<?php

    namespace App\Controller\Admin\GameManager;

    use App\Entity\Game;
    use App\Repository\CreatorRepository;
    use App\Service\VersioningService;
    use Doctrine\ORM\EntityManagerInterface;
    use JMS\Serializer\SerializationContext;
    use JMS\Serializer\SerializerInterface;
    use Nelmio\ApiDocBundle\Attribute\Model;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
    use Symfony\Component\Security\Http\Attribute\IsGranted;
    use Symfony\Component\Validator\Validator\ValidatorInterface;
    use Symfony\Contracts\Cache\TagAwareCacheInterface;
    use OpenApi\Attributes as OA;

    // Documentation
    #[OA\Post(summary: 'Création d\'un jeu par un administrateur')]
    #[OA\RequestBody(
        description: 'Création d\'un jeu par un administrateur',
        content: new OA\JsonContent(
            ref: new Model(type: Game::class, groups: ['getGame'])
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Le jeu a été créé succès par l\'administrateur.',
        content: new OA\JsonContent(
            ref: new Model(type: Game::class, groups: ['getGame'])
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Échec de la validation des données envoyées dans la requête.'
    )]
    #[OA\Response(
        response: 403,
        description: 'L\'utilisateur ne dispose pas du rôle requis'
    )]
    #[OA\Tag(name: 'Admin Manager')]


    class CreateGameController extends AbstractController
    {
        public function __construct(
            private readonly EntityManagerInterface $entityManager,
            private readonly ValidatorInterface $validator,
            private readonly SerializerInterface $serializer,
            private readonly TagAwareCacheInterface $cache,
            private readonly UrlGeneratorInterface $urlGenerator,
            private readonly VersioningService $versioningService,
            private readonly CreatorRepository $creatorRepository
        ){}


        #[Route(path: '/api/admin/games', name: 'admin_create_game', methods: ['POST'])]
        #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits requis pour créer un jeu')]
        public function createGame(Request $request): JsonResponse
        {
            // On décode le JSON en tableau associatif pour récupérer facilement les IDs des créateurs
            $data = json_decode($request->getContent(), true);

            $newGame = $this->serializer->deserialize($request->getContent(), Game::class, 'json');

            // Gestion de l'association Many-to-Many avec les créateurs existants
            if (isset($data['creatorIds']) && is_array($data['creatorIds'])) {
                foreach ($data['creatorIds'] as $creatorId) {
                    $creator = $this->creatorRepository->find($creatorId);

                    if ($creator) {
                        $newGame->addCreator($creator); // Méthode générée par MakerBundle dans l'entité Game
                    }
                }
            }

            // Validation
            $errors = $this->validator->validate($newGame);
            if ($errors->count() > 0) {
                return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            }

            // Invalidate tag
            $this->cache->invalidateTags(['gameTag', 'creatorTag']);

            $this->entityManager->persist($newGame);
            $this->entityManager->flush();

            $location = $this->urlGenerator->generate('game_details', ['id' => $newGame->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            // Context group
            $context = SerializationContext::create();
            $context->setGroups(['getGame']);

            // Version
            $version = $this->versioningService->getVersion();
            $context->setVersion($version);

            $newGameJson = $this->serializer->serialize($newGame, 'json', $context);

            return new JsonResponse($newGameJson, Response::HTTP_CREATED, ['Location' => $location, 'X-API-VERSION' => $version], true);
        }
    }
