<?php

    namespace App\Controller\GameManager;

    use App\Entity\Creator;
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
    #[OA\Patch(summary: 'Mise à jour d\'un jeu en fonction de son ID')]
    #[OA\RequestBody(
        description: 'Modification du jeu',
        content: new OA\JsonContent(
            ref: new Model(type: Game::class, groups: ['getGame'])
        )
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Identifiant du jeu à mettre à jour.',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Le jeu a été mis à jour avec succès.'
    )]
    #[OA\Response(
        response: 400,
        description: 'Échec de la validation des données modifiées dans la requête.'
    )]
    #[OA\Response(
        response: 401,
        description: 'L\'utilisateur n\'est pas un créateur valide.'
    )]
    #[OA\Response(
        response: 403,
        description: 'L\'utilisateur n\'a pas les droits nécessaires ou n\'est pas propriétaire du jeu.'
    )]
    #[OA\Response(
        response: 404,
        description: 'Le jeu demandé n\'a pas été trouvé'
    )]
    #[OA\Tag(name: 'Game Manager')]


    class UpdateGameController extends AbstractController
    {
        public function __construct(
            private readonly SerializerInterface $serializer,
            private readonly EntityManagerInterface $entityManager,
            private readonly ValidatorInterface $validator,
            private readonly TagAwareCacheInterface $cache
        ){}


        #[Route(path: '/api/games/{id}', name: 'update_game', methods: ['PATCH'])]
        #[IsGranted('ROLE_CREATOR')]
        public function updateGame(Game $currentGame, Request $request): JsonResponse
        {
            $creator = $this->getUser();
            if (!$creator instanceof Creator) {
                return new JsonResponse(['Erreur' => 'Créateur invalide'], Response::HTTP_UNAUTHORIZED);
            }

            if (!$currentGame->getCreators()->contains($creator)) {
                return new JsonResponse(['Erreur' => 'Vous ne pouvez pas modifier ce jeu'], Response::HTTP_FORBIDDEN);
            }

            $updatedGame = $this->serializer->deserialize($request->getContent(), Game::class, 'json');

            // PATCH game name
            if ($updatedGame->getName()) {
                $currentGame->setName($updatedGame->getName());
            }

            // PATCH game type
            if ($updatedGame->getType()) {
                $currentGame->setType($updatedGame->getType() ?? null);
            }

            // Validation
            $error = $this->validator->validate($currentGame);
            if ($error->count() > 0) {
                return new JsonResponse($this->serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            }

            // Invalidate tags
            $this->cache->invalidateTags(['gameTag', 'creatorTag']);

            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
    }
