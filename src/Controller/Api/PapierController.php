<?php

namespace App\Controller\Api;
use App\Service\NotificationService;
use App\Entity\Papier;
use App\Entity\User;
use App\Repository\PapierRepository;
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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @Route("/api")
 */
class PapierController extends AbstractController
{

    /**
     * @Route("/papiers/add_papier", name="add_papier", methods={"POST"})
     */
    public function new(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        NotificationService $notifier
    ): JsonResponse {

        $titre = $request->request->get('titre');
        $description = $request->request->get('description');
        $uploadedFile = $request->files->get('file');

        if (!$uploadedFile || !$uploadedFile->isValid()) {
            return new JsonResponse(['error' => 'Fichier manquant'], 400);
        }

        // Générer un nom unique et déplacer le fichier
        $fileName = uniqid() . '.' . $uploadedFile->guessExtension();

        $extension = $uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension();
        $fileName = uniqid() . '.' . $extension;



        // Création de l'entité Papier
        $papier = new Papier();
        $papier->setTitre($titre);
        $papier->setDescription($description);
        $papier->setFile('/uploads/' . $fileName); // URL publique pour le navigateur
        $papier->setCreatedAt(new \DateTime());
        $papier->setUpdatedAt(new \DateTime());
        $papier->setEtat(false);
        $papier->setUser($this->getUser());

        // Validation
        $errors = $validator->validate($papier);
        if (count($errors)) {
            $errorsJson = $serializer->serialize($errors, 'json');
            return new JsonResponse($errorsJson, 400, [
                'Content-Type' => 'application/json'
            ]);
        }

        // Persistance
        $entityManager->persist($papier);
        $entityManager->flush();


        return new JsonResponse([
            'status' => 201,
            'message' => 'Papier ajouté avec succès',
            'file_path' => '/uploads/' . $fileName
        ], 201);
    }

    /**
     * @Route("/papiers", name="list_papier", methods={"GET"})
     */
    public function getSendedPapiers(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $papiers = $entityManager->getRepository(Papier::class)->findBy(['etat' => true]);

        $data = $serializer->serialize($papiers, 'json', [
            'groups' => ['papier_read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);


        return new Response($data, 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/papiers/draftpapiers", name="listdraft_papier", methods={"GET"})
     */
    public function getDraftPapiers(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $papiers = $entityManager->getRepository(Papier::class)->findBy([
            'user' => ($this->getUser())->getId(),
            'etat' => false,
        ]);
        $jsonContent = $serializer->serialize($papiers, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
             }
         ]);
        return new Response($jsonContent, 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/papiers/update_papier/{id}", name="update_papier", methods={"PUT"})
     */
    public function update(Request $request, SerializerInterface $serializer, Papier $papier, ValidatorInterface $validator, EntityManagerInterface $entityManager)
    {
    
        $papierUpdate = $entityManager->getRepository(Papier::class)->find($papier->getId());
        $data = json_decode($request->getContent());
        foreach ($data as $key => $value){
            if($key && !empty($value)) {
                $name = ucfirst($key);
                $setter = 'set'.$name;
                $papierUpdate->$setter($value);
            }
        }
        $errors = $validator->validate($papierUpdate);
        if(count($errors)) {
            $errors = $serializer->serialize($errors, 'json');
            return new Response($errors, 500, [
                'Content-Type' => 'application/json'
            ]);
        }
        $entityManager->flush();
        $data = [
            'status' => 200,
            'message' => 'papier a bien été mis à jour'
        ];
        return new JsonResponse($data);
    }

    /**
     * @Route("/getPapier/{id}", name="get_papier", methods={"GET"})
     */
    public function getPapier(int $id, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        $papier = $entityManager->getRepository(Papier::class)->find($id);
    
        if (!$papier) {
            return new Response(json_encode(['error' => 'Papier not found']), 404, [
                'Content-Type' => 'application/json'
            ]);
        }
    
        $jsonData = $serializer->serialize($papier, 'json', [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId(); // retourne seulement l'id de l'objet lié
            }
        ]);
    
        return new Response($jsonData, 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/papiers/delete_papier/{id}", name="delete_papier", methods={"DELETE"})
     */
    public function delete(Papier $papier, EntityManagerInterface $entityManager)
    {
        $entityManager->remove($papier);
        $entityManager->flush();
        return new Response(null, 204);
    }

    /**
     * @Route("/papiers/send_papier/{id}", name="send_papier", methods={"PUT"})
     */
    public function sendPapier(Papier $papier, EntityManagerInterface $entityManager, NotificationService $notifier)
    {
        //soumettre papier(changement etat false à true )
        $papier = $entityManager->getRepository(Papier::class)->find($papier->getId());
    
        $papier->setEtat(true);
    
        $entityManager->flush();
        $data = [
            'status' => 200,
            'message' => 'papier est bien envoyé'
        ];
        $uploadDir = $this->getParameter('uploads_directory');
        $filePath = $uploadDir . '/' . basename($papier->getFile());

        if (!file_exists($filePath)) {
            return new JsonResponse(['error' => 'Fichier introuvable'], 500);
        }


        // Notification admin avec pièce jointe
        $authorName = $this->getUser()->getPrenom() . ' ' . $this->getUser()->getNom();
        $notifier->notifyAdminPaperSubmitted(
            'admin@example.com',
            $papier->getTitre(),
            $authorName,
            $filePath // ✅ on passe bien le chemin disque
        );

        return new JsonResponse($data);
    }
    
    /**
     * @Route("/papiers/assign_papier/{id}", name="assgin_papier", methods={"PUT"})
     */
    public function assignPapier(
        Request $request,
        Papier $papier,
        EntityManagerInterface $entityManager,
        NotificationService $notifier
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['expert_id'])) {
            return new JsonResponse(['message' => 'expert_id manquant'], 400);
        }

        // Ici on n'utilise pas Expert::class du tout
        $papier->setExpertId($data['expert_id']);
        $entityManager->flush();
        $expert = $entityManager->getRepository(User::class)->find($data['expert_id']);

        $notifier->notifyEditorAssigned($expert->getEmail(), $papier->getTitre());
        return new JsonResponse([
            'status' => 200,
            'message' => 'Papier bien assigné à l\'expert',
        ]);
    }

    /**
     * @Route("/papiers/getAssignedPapiers", name="assigned_papiers", methods={"GET"})
     */
    public function getAssignedPapiers(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {

        $papiers = $entityManager->getRepository(Papier::class)->findBy([
            'expert_id' => $this->getUser()->getId(),
            'etat' => true,
        ]);

        $jsonContent = $serializer->serialize($papiers, 'json', [
            'groups' => ['papier_read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);


        return new Response($jsonContent, 200, [
            'Content-Type' => 'application/json'
        ]);
    }

        /**
     * @Route("/papiers/{id}/experts", name="get_experts_of_papier", methods={"GET"})
     */
    public function getExpertsOfPapier(Papier $papier, SerializerInterface $serializer): JsonResponse
    {
        $experts = $papier->getExperts(); // relation ManyToMany
        $json = $serializer->serialize($experts, 'json', ['groups' => 'expert:read']);
        
        return new JsonResponse($json, 200, [], true);
    }


}