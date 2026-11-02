<?php

    namespace App\Controller\GameManager;

    use App\Entity\Creator;
    use App\Entity\Game;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;
    use Symfony\Contracts\Cache\TagAwareCacheInterface;
    use OpenApi\Attributes as OA;

    // Documentation
    #[OA\Delete(summary: 'Supprimer en jeu en fonction de son ID')]
    #[OA\Parameter(
        name: 'id',
        description: 'Identifiant du jeu à supprimer',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Le jeu a été supprimé avec succès.'
    )]
    #[OA\Response(
        response: 401,
        description: 'L\'utilisateur n\'est pas un Creator valide.'
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


    class DeleteGameController extends AbstractController
    {
        public function __construct(
            private readonly EntityManagerInterface $entityManager,
            private readonly TagAwareCacheInterface $cache

        ){}


        #[Route(path: '/api/games/{id}', name: 'delete_game', methods: ['DELETE'])]
        #[IsGranted('ROLE_CREATOR')]
        public function deleteGame(Game $currentGame): JsonResponse
        {
            $creator = $this->getUser();
            if (!$creator instanceof Creator) {
                return new JsonResponse(['Erreur' => 'Vous n\'êtes pas un créateur valide'], Response::HTTP_UNAUTHORIZED);
            }

            // Forbid the current creator to remove game if this one is not bind to this
            if (!$currentGame->getCreators()->contains($creator)) {
                return new JsonResponse(['Erreur' => 'Vous ne pouvez pas supprimer ce jeu'], Response::HTTP_FORBIDDEN);
            }

            // Invalidate tag
            $this->cache->invalidateTags(['creatorTag', 'gameTag']);

            $this->entityManager->remove($currentGame);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
    }
