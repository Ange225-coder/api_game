<?php

    namespace App\Controller\CreatorManager;

    use App\Entity\Creator;
    use App\Repository\CreatorRepository;
    use App\Service\VersioningService;
    use JMS\Serializer\SerializationContext;
    use JMS\Serializer\SerializerInterface;
    use Nelmio\ApiDocBundle\Attribute\Model;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Contracts\Cache\ItemInterface;
    use Symfony\Contracts\Cache\TagAwareCacheInterface;
    use OpenApi\Attributes as OA;

    // Documentation
    #[OA\Get(summary: 'Récupérer la liste des créateurs')]
    #[OA\Response(
        response: 200,
        description: 'La liste des créateurs a été récupérée',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(new Model(type: Creator::class, groups: ['getCreator']))
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Une erreur est survenue lors de la récupération des créateurs'
    )]
    #[OA\Tag(name: 'Creator Manager')]


    class AllCreatorsController extends AbstractController
    {
        public function __construct(
            private readonly SerializerInterface $serializer,
            private readonly TagAwareCacheInterface $cache,
            private readonly CreatorRepository $creatorRepository,
            private readonly VersioningService $versioningService
        ){}


        #[Route(path: '/api/creators', name: 'all_creators', methods: ['GET'])]
        public function allCreators(): JsonResponse
        {
            $context = SerializationContext::create();
            $context->setGroups(['getCreator']);

            // Version
            $version = $this->versioningService->getVersion();
            $context->setVersion($version);

            // Caching
            $idCache = 'get_creator_list';
            $creatorListJson = $this->cache->get($idCache, function (ItemInterface $item) use ($context) {

                // Checking the caching into the logs
                error_log('CACHING');

                $item->tag('creatorTag');

                $creatorList = $this->creatorRepository->findAll();

                return $this->serializer->serialize($creatorList, 'json', $context);
            });

            return new JsonResponse($creatorListJson, Response::HTTP_OK, ['X-API-VERSION' => $version], true);
        }
    }
