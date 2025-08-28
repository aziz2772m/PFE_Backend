<?php

namespace App\Controller\Api;
use App\Entity\Avis;
use App\Entity\Papier;
use App\Service\NotificationService;
use App\Repository\AvisRepository;
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
class AvisController extends AbstractController
{
  /**
     * @Route("/avis/add_avis/{papierId}", name="add_avis", methods={"POST"})
     */
    public function new(int $papierId, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $avis=$request->getContent();
        $avis = $serializer->deserialize($request->getContent(), Avis::class, 'json');

        $papier = $entityManager->getRepository(Papier::class)->find($papierId);
        if (!$papier) {
           return new JsonResponse(['message' => 'Papier non trouvé'], 404);
        }

        $avis->setUser( $this->getUser());// save user_id on avis
        $avis->setCreatedAt(new \DateTime());
        $avis->setUpdatedAt(new \DateTime());
        $avis->setEtat(false);
        $avis->setPapier($papier);
        $errors = $validator->validate($avis);
        if(count($errors)) {
            $errors = $serializer->serialize($errors, 'json');
            return new Response($errors, 500, [
                'Content-Type' => 'application/json'
            ]);
        }
        $entityManager->persist($avis);
        $entityManager->flush();
        $data = [
            'status' => 201,
            'message' => 'avis a bien été ajoutée'
        ];
        return new JsonResponse($data, 201);
    }
    /**
     * @Route("/avis", name="list_avis", methods={"GET"})
     */
    public function getSendedAvis(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $avis = $entityManager->getRepository(Avis::class)->findBy(array('etat' => true));
        $data = $serializer->serialize($avis, 'json');
        return new Response($data, 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/avis/draftavis", name="listdraft_avis", methods={"GET"})
     */
    public function getDraftAvis(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $avis = $entityManager->getRepository(Avis::class)->findBy(array('etat' => false));
        $data = $serializer->serialize($avis, 'json');
        return new Response($data, 200, [
            'Content-Type' => 'application/json'
        ]);
    }

        /**
     * @Route("/avis/{id}", name="get_avis", methods={"GET"})
     */
    public function getAvis(int $id, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        $avis = $entityManager->getRepository(Avis::class)->find($id);
    
        if (!$avis) {
            return new Response(json_encode(['error' => 'avis not found']), 404, [
                'Content-Type' => 'application/json'
            ]);
        }
    
        $jsonData = $serializer->serialize($avis, 'json', [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId(); // retourne seulement l'id de l'objet lié
            }
        ]);
    
        return new Response($jsonData, 200, [
            'Content-Type' => 'application/json'
        ]);
    }


    /**
     * @Route("/avis/update_avis/{id}", name="update_avis", methods={"PUT"})
     */
    public function update(Request $request, SerializerInterface $serializer, Avis $avis, ValidatorInterface $validator, EntityManagerInterface $entityManager)
    {
        $AvisUpdate = $entityManager->getRepository(Avis::class)->find($avis->getId());
        $data = json_decode($request->getContent());
        foreach ($data as $key => $value){
            if($key && !empty($value)) {
                $name = ucfirst($key);
                $setter = 'set'.$name;
                $AvisUpdate->$setter($value);
            }
        }
        $errors = $validator->validate($AvisUpdate);
        if(count($errors)) {
            $errors = $serializer->serialize($errors, 'json');
            return new Response($errors, 500, [
                'Content-Type' => 'application/json'
            ]);
        }
        $entityManager->flush();
        $data = [
            'status' => 200,
            'message' => 'avis a bien été mis à jour'
        ];
        return new JsonResponse($data);
    }

    /**
     * @Route("/avis/delete_avis/{id}", name="delete_avis", methods={"DELETE"})
     */
    public function delete(Avis $avis, EntityManagerInterface $entityManager)
    {
        $entityManager->remove($avis);
        $entityManager->flush();
        return new Response(null, 204);
    }

    /**
     * @Route("/avis/send_avis/{id}", name="send_avis", methods={"PUT"})
     */
    public function sendAvis(Avis $avis, EntityManagerInterface $entityManager, NotificationService $notifier)
    {
        //soumettre avis(changement etat false à true )
        $avis = $entityManager->getRepository(Avis::class)->find($avis->getId());
        $papier = $avis->getPapier();
        $researcher = $papier->getUser();
        $researcherEmail = $researcher->getEmail();
        $avis->setEtat(true);
    
        $notifier->notifyResearcherFinalDecision(
            $researcherEmail,
            $papier->getTitre(),
            $avis->getScore(),
            $avis->getCommentaire()
        );

        $entityManager->flush();
        $data = [
            'status' => 200,
            'message' => 'avis a bien envoyé'
        ];
        return new JsonResponse($data);
    }
}