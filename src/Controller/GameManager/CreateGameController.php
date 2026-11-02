<?php

    namespace App\Controller\GameManager;

    use App\Entity\Creator;
    use App\Entity\Game;
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
    #[OA\Post(summary: 'Créer un jeu')]
    #[OA\RequestBody(
        description: 'Création d\'un jeu',
        content: new OA\JsonContent(
            ref: new Model(type: Game::class, groups: ['getGame'])
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Un jeu vient d\'être ajouté.',
        content: new OA\JsonContent(
            ref: new Model(type: Game::class, groups: ['getGame'])
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Échec de la validation des données envoyées dans la requête.',
    )]
    #[OA\Response(
        response: 401,
        description: 'L\'utilisateur n\'est pas un créateur valide.',
    )]
    #[OA\Response(
        response: 403,
        description: 'L\'utilisateur ne dispose pas du rôle requis.',
    )]
    #[OA\Tag(name: 'Game Manager')]


    class CreateGameController extends AbstractController
    {
        public function __construct(
            private readonly EntityManagerInterface $entityManager,
            private readonly ValidatorInterface $validator,
            private readonly SerializerInterface $serializer,
            private readonly TagAwareCacheInterface $cache,
            private readonly UrlGeneratorInterface $urlGenerator,
            private readonly VersioningService $versioningService
        ){}


        #[Route(path: '/api/games', name: 'create_game', methods: ['POST'])]
        #[IsGranted('ROLE_CREATOR')]
        public function createGame(Request $request): JsonResponse
        {
            $creator = $this->getUser();
            if (!$creator instanceof Creator) {
                return new JsonResponse(['Erreur' => 'Ce créateur n\'est pas valide'], Response::HTTP_UNAUTHORIZED);
            }

            $newGame = $this->serializer->deserialize($request->getContent(), Game::class, 'json');

            // Validation
            $error = $this->validator->validate($newGame);
            if ($error->count() > 0) {
                return new JsonResponse($this->serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            }

            // Bind game to the logged creator
            $newGame->addCreator($creator);

            // Invalidate tag if it exists
            $this->cache->invalidateTags(['gameTag', 'creatorTag']);

            $this->entityManager->persist($newGame);
            $this->entityManager->flush();

            // Group context
            $context = SerializationContext::create();
            $context->setGroups(['getGame']);

            // Version
            $version = $this->versioningService->getVersion();
            $context->setVersion($version);

            $location = $this->urlGenerator->generate('game_details', ['id' => $newGame->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            $newGameJson = $this->serializer->serialize($newGame, 'json', $context);

            return new JsonResponse($newGameJson, Response::HTTP_CREATED, ['Location' => $location, 'X-API-VERSION' => $version], true);
        }
    }
