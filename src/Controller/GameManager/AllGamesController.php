<?php

    namespace App\Controller\GameManager;

    use App\Entity\Game;
    use App\Repository\GameRepository;
    use App\Service\VersioningService;
    use JMS\Serializer\SerializationContext;
    use JMS\Serializer\SerializerInterface;
    use Nelmio\ApiDocBundle\Attribute\Model;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Contracts\Cache\ItemInterface;
    use Symfony\Contracts\Cache\TagAwareCacheInterface;
    use OpenApi\Attributes as OA;

    //Documentation
    #[OA\Get(summary: 'Récupérer la liste des jeux')]
    #[OA\Parameter(
        name: 'page',
        description: 'Définir la page à afficher',
        in: 'query',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Définir le nombre d\'éléments à afficher par page',
        in: 'query',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Response(
        response: 200,
        description: 'La liste des jeux a été récupéré avec succès.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(new Model(type: Game::class, groups: ['getGame']))
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Une erreur serveur est survenue'
    )]
    #[OA\Tag(name: 'Game Manager')]


    class AllGamesController extends AbstractController
    {
        public function __construct(
            private readonly TagAwareCacheInterface $cache,
            private readonly SerializerInterface $serializer,
            private readonly GameRepository $gameRepository,
            private readonly VersioningService $versioningService
        ){}


        #[Route(path: '/api/games', name: 'all_games', methods: ['GET'])]
        public function allGames(Request $request): JsonResponse
        {
            $page = $request->query->get('page', 1);
            $limit= $request->query->get('limit', 3);

            // Context group
            $context = SerializationContext::create();
            $context->setGroups(['getGame']);

            // Caching
            $idCache = 'get_all_game_'.$page.'_'.$limit;
            $jsonGameList = $this->cache->get($idCache, function (ItemInterface $item) use ($page, $limit, $context) {

                // Show caching into the logs
                error_log('CACHING');

                $item->tag('gameTag');

                $gameList = $this->gameRepository->findAllWithPagination($page, $limit);

                return $this->serializer->serialize($gameList, 'json', $context);
            });

            // Version
            $version = $this->versioningService->getVersion();
            $context->setVersion($version);

            return new JsonResponse($jsonGameList, Response::HTTP_OK, ['X-API-VERSION' => $version], true);
        }
    }
