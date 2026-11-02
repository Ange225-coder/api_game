<?php

    namespace App\Controller\CreatorManager;

    use App\Entity\Creator;
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
    #[OA\Get(summary: 'Récupérer un créateur en fonction de son ID')]
    #[OA\Parameter(
        name: 'id',
        description: 'Identifiant du créateur',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Un créateur vient d\'être récupérer',
        content: new OA\JsonContent(
            ref: new Model(type: Creator::class, groups: ['getCreator'])
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Le créateur fournis n\'a pas été trouvé'
    )]
    #[OA\Tag(name: 'Creator Manager')]


    class CreatorDetailsController extends AbstractController
    {
        public function __construct(
            private readonly SerializerInterface $serializer,
            private readonly VersioningService $versioningService
        ){}


        #[Route(path: '/api/creators/{id}', name: 'creator_details', methods: ['GET'])]
        public function creatorDetails(Creator $creator): JsonResponse
        {
            $context = SerializationContext::create();
            $context->setGroups('getCreator');

            // Version
            $version = $this->versioningService->getVersion();
            $context->setVersion($version);

            $creatorDetailsJson = $this->serializer->serialize($creator, 'json', $context);

            return new JsonResponse($creatorDetailsJson, Response::HTTP_OK, ['API-X-VERSION' => $version], true);
        }
    }
