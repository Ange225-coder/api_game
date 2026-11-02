<?php

    namespace App\Controller\GameManager;

    use App\Entity\Game;
    use App\Service\VersioningService;
    use JMS\Serializer\SerializationContext;
    use JMS\Serializer\SerializerInterface;
    use Nelmio\ApiDocBundle\Attribute\Model;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use OpenApi\Attributes as OA;

    // Documentation
    #[OA\Get(summary: 'Récupérer un jeu en fonction de son ID')]
    #[OA\Parameter(
        name: 'id',
        description: 'L\'identifiant du jeu à récupérer',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Les détails du jeu ont été récupérés et sérialisés avec succès.',
        content: new OA\JsonContent(
            ref: new Model(type: Game::class, groups: ['getGame'])
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Le jeu demandé n\'a pas été trouvé'
    )]
    #[OA\Tag(name: 'Game Manager')]


    class GameDetailsController extends AbstractController
    {
        public function __construct(
            private readonly SerializerInterface $serializer,
            private readonly VersioningService $versioningService
        ){}


        #[Route(path: '/api/games/{id}', name: 'game_details', methods: ['GET'])]
        public function gameDetails(Game $game): JsonResponse
        {
            // Group context
            $context = SerializationContext::create();
            $context->setGroups(['getGame']);

            // Version
            $version = $this->versioningService->getVersion();
            $context->setVersion($version);

            $gameDetailsJson = $this->serializer->serialize($game, 'json', $context);

            return new JsonResponse($gameDetailsJson, Response::HTTP_OK, ['X-API-VERSION' => $version], true);
        }
    }
