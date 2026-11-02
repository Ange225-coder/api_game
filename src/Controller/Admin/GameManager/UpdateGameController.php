<?php

    namespace App\Controller\Admin\GameManager;

    use App\Entity\Game;
    use Doctrine\ORM\EntityManagerInterface;
    use JMS\Serializer\SerializerInterface;
    use Nelmio\ApiDocBundle\Attribute\Model;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;
    use Symfony\Component\Validator\Validator\ValidatorInterface;
    use Symfony\Contracts\Cache\TagAwareCacheInterface;
    use OpenApi\Attributes as OA;

    // Documentation
    #[OA\Patch(summary: 'Mettre un jeu à jour en fonction de ID par un administrateur')]
    #[OA\Parameter(
        name: 'id',
        description: 'Identifiant du jeu à mettre à jour',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'Mettre un jeu à jour en fonction de ID par un administrateur',
        content: new OA\JsonContent(
            ref: new Model(type: Game::class, groups: ['getGame'])
        )
    )]
    #[OA\Response(
        response: 204,
        description: 'Le jeu a été mis à jour avec succès par l\'administrateur.'
    )]
    #[OA\Response(
        response: 400,
        description: 'Échec de la validation des données modifiées dans la requête.'
    )]
    #[OA\Response(
        response: 403,
        description: 'L\'utilisateur ne dispose pas du rôle requis'
    )]
    #[OA\Response(
        response: 404,
        description: 'Le jeu correspondant à l\'identifiant n\'a pas été trouvé'
    )]
    #[OA\Tag(name: 'Admin Manager')]


    class UpdateGameController extends AbstractController
    {
        public function __construct(
            private readonly EntityManagerInterface $entityManager,
            private readonly SerializerInterface $serializer,
            private readonly ValidatorInterface $validator,
            private readonly TagAwareCacheInterface $cache
        ){}


        #[Route(path: '/api/admin/games/{id}', name: 'admin_update_game', methods: ['PATCH'])]
        #[IsGranted('ROLE_ADMIN')]
        public function updateGame(Request $request, Game $currentGame): JsonResponse
        {
            $updateGame = $this->serializer->deserialize($request->getContent(), Game::class, 'json');

            // PATCH for game name
            if ($updateGame->getName()) {
                $currentGame->setName($updateGame->getName());
            }

            // PATCH for game type
            if ($updateGame->getType()) {
                $currentGame->setType($updateGame->getType());
            }

            // PATCH for number of players
            if ($updateGame->getNumberOfPlayers()) {
                $currentGame->setNumberOfPlayers($updateGame->getNumberOfPlayers());
            }

            // Validation
            $error = $this->validator->validate($currentGame);
            if ($error->count() > 0) {
                return new JsonResponse($this->serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            }

            // invalidate tag
            $this->cache->invalidateTags(['gameTag', 'creatorTag']);

            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
    }
