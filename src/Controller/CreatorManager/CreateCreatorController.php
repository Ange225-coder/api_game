<?php

    namespace App\Controller\CreatorManager;

    use App\Entity\Creator;
    use App\Service\VersioningService;
    use Doctrine\ORM\EntityManagerInterface;
    use JMS\Serializer\SerializationContext;
    use JMS\Serializer\SerializerInterface;
    use Nelmio\ApiDocBundle\Attribute\Model;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
    use Symfony\Component\Validator\Validator\ValidatorInterface;
    use Symfony\Contracts\Cache\TagAwareCacheInterface;
    use OpenApi\Attributes as OA;

    // Documentation
    #[OA\Post(summary: 'Créer un nouveau créateur')]
    #[OA\RequestBody(
        description: 'Création d\'un créateur',
        content: new OA\JsonContent(
            ref: new Model(type: Creator::class, groups: ['getCreator'])
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Un créateur vient d\'être ajouté',
        content: new OA\JsonContent(
            ref: new Model(type: Creator::class, groups: ['getCreator'])
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Les données envoyées sont invalides ou ne respectent pas les contraintes de validation.'
    )]
    #[OA\Response(
        response: 500,
        description: 'Erreur lors de la création du créateur'
    )]
    #[OA\Tag(name: 'Creator Manager')]


    class CreateCreatorController extends AbstractController
    {
        public function __construct(
            private readonly EntityManagerInterface $entityManager,
            private readonly ValidatorInterface $validator,
            private readonly SerializerInterface $serializer,
            private readonly UrlGeneratorInterface $urlGenerator,
            private readonly VersioningService $versioningService,
            private readonly TagAwareCacheInterface $cache,
            private readonly UserPasswordHasherInterface $passwordHasher
        ){}


        #[Route(path: '/api/creators', name: 'create_creator', methods: ['POST'])]
        public function createCreator(Request $request): JsonResponse
        {
            $newCreator = $this->serializer->deserialize($request->getContent(), Creator::class, 'json');

            // Hashed password
            $passwordHashed = $this->passwordHasher->hashPassword($newCreator, $newCreator->getPassword());
            $newCreator->setPassword($passwordHashed);

            // Validation
            $error = $this->validator->validate($newCreator);
            if ($error->count() > 0) {
                return new JsonResponse($this->serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            }

            // Invalidate tag if cache tag exists
            $this->cache->invalidateTags(['creatorTag']);

            $this->entityManager->persist($newCreator);
            $this->entityManager->flush();

            $context = SerializationContext::create();
            $context->setGroups(['getCreator']);

            // Version
            $version = $this->versioningService->getVersion();
            $context->setVersion($version);

            $location = $this->urlGenerator->generate('creator_details', ['id' => $newCreator->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            $newCreatorJson = $this->serializer->serialize($newCreator, 'json', $context);

            return new JsonResponse($newCreatorJson, Response::HTTP_CREATED, ['Location' => $location, 'X-API-VERSION' => $version], true);
        }
    }
