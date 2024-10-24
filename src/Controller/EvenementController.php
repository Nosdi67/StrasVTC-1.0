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
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

class EvenementController extends AbstractController
{
#[Route('/StrasVTC/planning', name: 'app_planning')]
public function calendar(): Response
{
    return $this->render('chauffeur/calendar.html.twig');
}

#[Route('/StrasVTC/planning/add-event', name: 'fc_add_event', methods: ['POST'])]
public function addEvent(Request $request, EntityManagerInterface $em, ChauffeurRepository $chauffeurRepository,CsrfTokenManagerInterface $csrfTokenManager): Response
{
    $event = new Evenement();
    $eventForm = $this->createForm(EventFormType::class);
    $eventForm->handleRequest($request);

    $csrfToken = $request->request->get('_token');
    if ($csrfTokenManager->isTokenValid(new CsrfToken('add_event', $csrfToken))) {
    return new Response('Invalid CSRF token', Response::HTTP_BAD_REQUEST);}

    $chauffeurId = $request->get('chauffeur_id');
    $chauffeur = $chauffeurRepository->find($chauffeurId);

    if ($eventForm->isSubmitted() && $eventForm->isValid()) {
        $event->setChauffeur($chauffeur);
        $event->setTitre($eventForm->get('titre')->getData());
        $event->setDebut($eventForm->get('debut')->getData());
        $event->setFin($eventForm->get('fin')->getData());

        // Sauvegarder l'événement
        $em->persist($event);
        $em->flush();

    }
    $this->addFlash('success', 'Événement ajouté avec succès.');
    return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
}
    #[Route('/StrasVTC/planning/get-event/{id}', name: 'fc_load_events', methods: ['POST'])]
    public function loadEvents(EvenementRepository $evenementRepository,ChauffeurRepository $chauffeurRepository, Chauffeur $chauffeur): Response
    {   
    $chauffeur = $chauffeurRepository->find($chauffeur);// recupere le chauffeur
    $events = $evenementRepository->findBy(['chauffeur' => $chauffeur]);// trouver les evenements du chauffeur
    $responseData = []; // tableau pour stocker les données de réponse

    foreach ($events as $event) {
        $responseData[] = [
            'id' => $event->getId(),
            'title' => $event->getTitre(),
            'start' => $event->getDebut()->format('Y-m-d H:i:s'),
            'end' => $event->getFin()->format('Y-m-d H:i:s'),
            'chauffeur' => $event->getChauffeur()->getNom(),
            'chauffeurId' => $event->getChauffeur()->getId(),
        ];
    }

    return new JsonResponse($responseData);
    }
    #[Route('/StrasVTC/planning/edit-event/', name: 'fc_edit_event', methods: ['POST'])]
    public function editEvent(Request $request, EntityManagerInterface $em, ChauffeurRepository $chauffeurRepository, EvenementRepository $evenementRepository,CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $eventForm = $this->createForm(EventFormType::class);
        $eventForm->handleRequest($request);

        // Vérifier le token CSRF
        $csrfToken = $request->request->get('_token');
        if ($csrfTokenManager->isTokenValid(new CsrfToken('edit_event', $csrfToken))) {
        return new Response('Invalid CSRF token', Response::HTTP_BAD_REQUEST);}

        $chauffeurId = $request->get('chauffeur_id');
        $chauffeur = $chauffeurRepository->find($chauffeurId);
        $eventId = $request->request->get('eventId');
        $event = $evenementRepository->find($eventId);

        if ($eventForm->isSubmitted() && $eventForm->isValid()) {
            $event->setTitre($eventForm->get('titre')->getData());
            $event->setDebut($eventForm->get('debut')->getData());
            $event->setFin($eventForm->get('fin')->getData());
            $event->setChauffeur($chauffeur);

            // Mettre à jour l'événement
            $em->flush();
            $this->addFlash('success', 'Événement modifié avec succès.');
            return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
        }

        return new Response('Form invalid', Response::HTTP_BAD_REQUEST);
    }
    #[Route('/StrasVTC/planning/delete-event', name: 'fc_delete_event', methods: ['POST'])]
    public function deleteEvent(Request $request, EntityManagerInterface $em, ChauffeurRepository $chauffeurRepository, EvenementRepository $evenementRepository,CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        // Vérifier le token CSRF
        $csrfToken = $request->request->get('_token');
        if ($csrfTokenManager->isTokenValid(new CsrfToken('delete_event', $csrfToken))) {
        return new Response('Invalid CSRF token', Response::HTTP_BAD_REQUEST);}

        $eventId = $request->request->get('eventId');
        $event = $evenementRepository->find($eventId);

        if ($event) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Événement supprimé avec succès.');
            return $this->redirectToRoute('app_chauffeur_info', ['id' => $event->getChauffeur()->getId()]);
        }

        return new Response('Event not found', Response::HTTP_NOT_FOUND);
    }
}
