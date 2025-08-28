<?php

namespace App\Controller\Api;
use App\Entity\User;
use App\Entity\Avis;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @Route("/api")
 */
class ExpertController extends AbstractController
{
  /**
     * @Route("/experts/add_expert", name="add_expert", methods={"POST"})
     */
    public function new(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator,UserPasswordEncoderInterface $passwordEncoder)
    {
        $expert=$request->getContent();
        $expert = $serializer->deserialize($request->getContent(), User::class, 'json');
        $expert->setCreatedAt(new \DateTime());
        $expert->setUpdatedAt(new \DateTime());
        $expert->setRoles(['ROLE_EXPERT']);
        // ✅ Encodage du mot de passe
        $encodedPassword = $passwordEncoder->encodePassword($expert, $expert->getPassword());
    
        $expert->setPassword($encodedPassword);
        $errors = $validator->validate($expert);
        if(count($errors)) {
            $errors = $serializer->serialize($errors, 'json');
            return new Response($errors, 500, [
                'Content-Type' => 'application/json'
            ]);
        }
        $entityManager->persist($expert);
        $entityManager->flush();
        $data = [
            'status' => 201,
            'message' => 'expert a bien été ajouté'
        ];
        return new JsonResponse($data, 201);
    }
    /**
     * @Route("/experts/update_expert/{id}", name="update_expert", methods={"PUT"})
     */
    public function update(Request $request, SerializerInterface $serializer, User $expert, ValidatorInterface $validator, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        $expertUpdate = $entityManager->getRepository(User::class)->find($expert->getId());
        $data = json_decode($request->getContent());

        $mapping = [
            'firstName'   => 'setPrenom',
            'lastName'    => 'setNom',
            'dateOfBirth' => 'setDateNaissance',
            'sexe'        => 'setSexe',
            'username'    => 'setEmail',
            'password'    => 'setPassword'
        ];

        foreach ($data as $key => $value) {
            if (!empty($value) && isset($mapping[$key])) {
                // 🔐 Traitement particulier pour le mot de passe
                if ($key === 'password') {
                    $encoded = $passwordEncoder->encodePassword($expertUpdate, $value);
                    $expertUpdate->setPassword($encoded);
                } else {
                    $setter = $mapping[$key];
                    $expertUpdate->$setter($value);
                }

            }
        }
        $errors = $validator->validate($expertUpdate);
        if(count($errors)) {
            $errors = $serializer->serialize($errors, 'json');
            return new Response($errors, 500, [
                'Content-Type' => 'application/json'
            ]);
        }
        $entityManager->flush();
        $data = [
            'status' => 200,
            'message' => 'expert a bien été mis à jour'
        ];
        return new JsonResponse($data);
    }
    
    /**
     * @Route("/experts/getExpert/{id}", name="get_expert", methods={"GET"})
     */
    public function getExpert(int $id, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        $expert = $entityManager->getRepository(User::class)->find($id);
    
        if (!$expert) {
            return new Response(json_encode(['error' => 'Papier not found']), 404, [
                'Content-Type' => 'application/json'
            ]);
        }
    
        $jsonData = $serializer->serialize($expert, 'json', [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId(); // retourne seulement l'id de l'objet lié
            }
        ]);
    
        return new Response($jsonData, 200, [
            'Content-Type' => 'application/json'
        ]);
    }


    /**
     * @Route("/experts/delete_expert/{id}", name="delete_expert", methods={"DELETE"})
     */
    public function delete(User $expert, EntityManagerInterface $entityManager)
    {
        // 1️⃣ Récupère tous les avis liés à cet expert
        $avisRepository = $entityManager->getRepository(Avis::class);
        $avisList = $avisRepository->findBy(['user' => $expert]);

        // 2️⃣ Supprime chaque avis
        foreach ($avisList as $avis) {
            $entityManager->remove($avis);
        }

        $entityManager->remove($expert);
        $entityManager->flush();
        return new Response(null, 204);
    }
        /**
     * @Route("/experts", name="list_experts", methods={"GET"})
     */
    public function getExperts(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $role='ROLE_EXPERT';
        $qb = $entityManager->createQueryBuilder();
        $qb->select('u')
        ->from(User::class, 'u')
        ->where('u.roles LIKE :roles')
        ->setParameter('roles', '%"'.$role.'"%')
        ;
        $ok= $qb->getQuery()->getResult();
        $jsonContent = $serializer->serialize($ok, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object;
             }
         ]);
        return new Response($jsonContent, 200, [
            'Content-Type' => 'application/json'
        ]);
    }





}

 

