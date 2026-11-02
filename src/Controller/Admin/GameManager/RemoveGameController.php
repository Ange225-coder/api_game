<?php

    namespace App\Controller\Admin\GameManager;

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
    #[OA\Delete(summary: 'Supprimer un jeu par un administrateur')]
    #[OA\Parameter(
        name: 'id',
        description: 'Identifiant du livre à supprimer',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Le jeu a été supprimé avec succès par l\'administrateur.'
    )]
    #[OA\Response(
        response: 403,
        description: 'L\'utilisateur ne dispose pas du rôle requis'
    )]
    #[OA\Response(
        response: 404,
        description: 'Le jeu correspondant à l\'identifiant fourni est introuvable.'
    )]
    #[OA\Tag(name: 'Admin Manager')]


    class RemoveGameController extends AbstractController
    {
        public function __construct(
            private readonly EntityManagerInterface $entityManager,
            private readonly TagAwareCacheInterface $cache
        ){}


        #[Route(path: '/api/admin/games/{id}', name: 'admin_delete_game', methods: ['DELETE'])]
        #[IsGranted('ROLE_ADMIN')]
        public function removeGame(Game $currentGame): JsonResponse
        {
            // Invalidate tag
            $this->cache->invalidateTags(['gameTag', 'creatorTag']);

            $this->entityManager->remove($currentGame);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
    }
