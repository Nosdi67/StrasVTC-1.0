<?php

namespace App\Controller;

use App\Entity\Chauffeur;
use App\Entity\Evenement;
use App\Form\EventFormType;
use App\Repository\PlanningRepository;
use App\Repository\ChauffeurRepository;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EvenementController extends AbstractController
{
#[Route('/StrasVTC/planning', name: 'app_planning')]
public function calendar(): Response
{
    return $this->render('chauffeur/calendar.html.twig');
}

#[Route('/StrasVTC/planning/add-event', name: 'fc_add_event', methods: ['POST'])]
public function addEvent(Request $request, EntityManagerInterface $em, ChauffeurRepository $chauffeurRepository): Response
{
    $event = new Evenement();
    $eventForm = $this->createForm(EventFormType::class, $event);
    $eventForm->handleRequest($request);
    // dd($request);
    $chauffeurId = $request->get('chauffeur_id');
    $chauffeur = $chauffeurRepository->find($chauffeurId);
    if ($eventForm->isSubmitted() && $eventForm->isValid()) {
        $event->setChauffeur($chauffeur);
        $event->setTitre($eventForm->get('titre')->getData());
        $event->setDebut($eventForm->get('debut')->getData());
        $event->setFin($eventForm->get('fin')->getData());

        $em->persist($event);
        $em->flush();

        return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
    }

    return new Response('Form invalid', Response::HTTP_FORBIDDEN);
    }
    #[Route('/StrasVTC/planning/edit-event/{id}', name: 'fc_load_events', methods: ['POST'])]
    public function loadEvents(EvenementRepository $evenementRepository,ChauffeurRepository $chauffeurRepository, Chauffeur $chauffeur): Response
    {   
    $chauffeur = $chauffeurRepository->find($chauffeur);
    $events = $evenementRepository->findBy(['chauffeur' => $chauffeur]);
    $responseData = []; // Utilisez une autre variable pour stocker les données de réponse

    foreach ($events as $event) {
        $responseData[] = [
            'id' => $event->getId(),
            'title' => $event->getTitre(),
            'start' => $event->getDebut()->format('Y-m-d H:i:s'),
            'end' => $event->getFin() ? $event->getFin()->format('Y-m-d H:i:s') : null,
            'chauffeur' => $event->getChauffeur()->getNom(),
            'chauffeurId' => $event->getChauffeur()->getId(),
        ];
    }

    return new JsonResponse($responseData);
    }
    #[Route('/StrasVTC/planning/edit-event/', name: 'fc_edit_event', methods: ['POST'])]
    public function editEvent(Request $request, EntityManagerInterface $em, ChauffeurRepository $chauffeurRepository, EvenementRepository $evenementRepository): Response
    {
        $eventForm = $this->createForm(EventFormType::class);
        $eventForm->handleRequest($request);
        
        $chauffeurId = $request->get('chauffeur_id');
        $chauffeur = $chauffeurRepository->find($chauffeurId);
        $eventId = $request->request->get('eventId');
        $event = $evenementRepository->find($eventId);
        
        if($eventForm ->isSubmitted() && $eventForm -> isvalid()){
            $event->setTitre($eventForm->get('titre')->getData());
            $event->setDebut($eventForm->get('debut')->getData());
            $event->setFin($eventForm->get('fin')->getData());
            $event->setChauffeur($chauffeur);
            $em->flush();
            return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]); 
        }
        
    }
    #[Route('/StrasVTC/planning/delete-event', name: 'fc_delete_event', methods: ['POST'])]
    public function deleteEvent(Request $request, EntityManagerInterface $em, ChauffeurRepository $chauffeurRepository, EvenementRepository $evenementRepository): Response
    {
        $eventId = $request->request->get('eventId');
        $event = $evenementRepository->find($eventId);
        $em->remove($event);
        $em->flush();
        return $this->redirectToRoute('app_chauffeur_info', ['id' => $event->getChauffeur()->getId()]);
    }
}
