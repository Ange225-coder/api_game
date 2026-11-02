<?php

    namespace App\Controller\CreatorManager;

    use App\Entity\Creator;
    use Doctrine\ORM\EntityManagerInterface;
    use JMS\Serializer\SerializerInterface;
    use Nelmio\ApiDocBundle\Attribute\Model;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;
    use Symfony\Component\Validator\Validator\ValidatorInterface;
    use Symfony\Contracts\Cache\TagAwareCacheInterface;
    use OpenApi\Attributes as OA;

    // Documentation
    #[OA\Put(summary: 'Mettre les données d\'un créateur à jour')]
    #[OA\RequestBody(
        description: 'Mise à jour d\'un créateur',
        content: new OA\JsonContent(
            ref: new Model(type: Creator::class, groups: ['getCreator'])
        )
    )]
    #[OA\Response(
        response: 204,
        description: 'Le profil du créateur a été mis à jour avec succès'
    )]
    #[OA\Response(
        response: 400,
        description: 'Les données envoyées sont invalides ou ne respectent pas les contraintes de validation.'
    )]
    #[OA\Response(
        response: 401,
        description: 'L\'utilisateur n\'est pas authentifié en tant que Creator valide.'
    )]
    #[OA\Response(
        response: 403,
        description: 'L\'utilisateur n\'est pas les droits nécessaires pour effectuer cette action.'
    )]
    #[OA\Tag(name: 'Creator Manager')]


    class UpdateCreatorController extends AbstractController
    {
        public function __construct(
            private readonly SerializerInterface $serializer,
            private readonly EntityManagerInterface $entityManager,
            private readonly ValidatorInterface $validator,
            private readonly UserPasswordHasherInterface $passwordHasher,
            private readonly TagAwareCacheInterface $cache
        ){}


        #[Route(path: '/api/creators/me', name: 'update_creator', methods: ['PUT'])]
        #[IsGranted('ROLE_CREATOR', message: 'Vous n\'avez pas les droits nécéssaires pour éffectuer cette action')]
        public function updateCreator(Request $request): JsonResponse
        {
            $creator = $this->getUser();

            if (!$creator instanceof Creator) {
                return new JsonResponse(['Erreur' => 'Vous n\'avez pas autorisé à effectuer des actions'], Response::HTTP_UNAUTHORIZED);
            }

            $creatorUpdated = $this->serializer->deserialize($request->getContent(), Creator::class, 'json');

            if ($creatorUpdated->getEmail()) {
                $creator->setEmail($creatorUpdated->getEmail());
            }

            if ($creatorUpdated->getPassword()) {
                $creator->setPassword($this->passwordHasher->hashPassword($creator, $creatorUpdated->getPassword()));
            }

            if ($creatorUpdated->getLastName()) {
                $creator->setLastName($creatorUpdated->getLastName());
            }

            if ($creatorUpdated->getFirstName()) {
                $creator->setFirstName($creatorUpdated->getFirstName());
            }

            // Validation
            $errors = $this->validator->validate($creator);
            if ($errors->count() > 0) {
                return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            }

            // Invalidate tag if cache tag exists
            $this->cache->invalidateTags(['creatorTag']);

            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
    }
