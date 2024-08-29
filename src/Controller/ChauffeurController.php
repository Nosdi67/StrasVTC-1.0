<?php

namespace App\Controller;

use App\Entity\Planning;
use App\Entity\Vehicule;
use App\Entity\Chauffeur;
use App\Entity\Evenement;
use App\Form\EventFormType;
use App\Form\PlanningFormType;
use App\Repository\ChauffeurRepository;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ChauffeurController extends AbstractController
{
    #[Route('/chauffeur', name: 'app_chauffeur')]
    public function index(ChauffeurRepository $chauffeurRepository): Response
    {
        $chauffeurs = $chauffeurRepository->findAll();
        return $this->render('chauffeur/index.html.twig', [
            'chauffeurs' => $chauffeurs
        ]);
    }
    #[Route('/chauffeur/profile/{id}', name: 'app_chauffeur_info')]
    public function info(Chauffeur $chauffeur, ChauffeurRepository $chauffeurRepository,EvenementRepository $evenementRepository): Response
    {
        $id = $chauffeur->getId();
        $chauffeur = $chauffeurRepository->find($id);
        $events = $evenementRepository -> findAll($chauffeur);
        $addForm = $this->createForm(EventFormType::class, new Evenement());
        $editForm =$this -> createForm(EventFormType::class);
        
        
        
        return $this->render('chauffeur/profileChauffeur.html.twig', [
            'chauffeur'=> $chauffeur,
            'events' => $events,
            'addForm' => $addForm->createView(),
            'editForm' => $editForm -> createView()
        ]);
    }
    #[Route('/chauffeur/profile/{id}/edit', name: 'app_chauffeur_profile_edit', methods: ['POST'])]
    public function profile(Chauffeur $chauffeur, ChauffeurRepository $chauffeurRepository,EntityManagerInterface $entityManagerInterface, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('profile_edit', $csrfToken))) {
            return new Response('Token CSRF invalide', Response::HTTP_FORBIDDEN);
        }

        $id = $chauffeur->getId();
        $chauffeur = $chauffeurRepository->find($id);

        // Récupérer les données envoyées par le formulaire
        $nom = filter_var($request->request->get('nom'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $prenom = filter_var($request->request->get('prenom'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL);
        $dateNaissance = filter_var($request->request->get('dateNaissance'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $sexe = filter_var($request->request->get('sexe'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
        // Mettre à jour l'utilisateur
        $chauffeur->setNom($nom);
        $chauffeur->setPrenom($prenom);
        $chauffeur->setEmail($email);
        $chauffeur->setDateNaissance(new \DateTime($dateNaissance));
        $chauffeur->setSexe($sexe);

        // Sauvegarder les modifications
        $entityManagerInterface->persist($chauffeur);
        $entityManagerInterface->flush();
    
        return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
    }
    #[Route('/chauffeur/profile/{id}/editProfilePIcture', name: 'app_chauffeur_profile_edit_picture', methods: ['POST'])]
    public function editProfilePicture(Chauffeur $chauffeur, ChauffeurRepository $chauffeurRepository,EntityManagerInterface $entityManagerInterface, Request $request, SluggerInterface $slugger, CsrfTokenManagerInterface $csrfTokenManager): Response    
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('profile_image_change', $csrfToken))) {
            return new Response('Token CSRF invalide', Response::HTTP_FORBIDDEN);
        }

        $chauffeur = $chauffeurRepository->find($chauffeur->getId());
        $image= $request->files->get('profileImage');
        $uploadDir = $this->getParameter('profile_directory');
        
        if ($image->getSize() > 10485760 ){
            $this->addFlash('danger', 'La taille de l\'image ne doit pas dépasser 10Mo');
            return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
        }
        $originalFileName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFileName = $slugger->slug($originalFileName);
        $newFileName = $safeFileName.'-'.uniqid().'.'.$image->guessExtension();


        $image->move($uploadDir, $newFileName);

        $chauffeur->setImage($newFileName);

        $entityManagerInterface->persist($chauffeur);
        $entityManagerInterface->flush();

        return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
    }
    #[Route('/chauffeur/profile/{id}/delete', name: 'app_chauffeur_profile_delete', methods: ['POST'])]
    public function delete(Chauffeur $chauffeur, ChauffeurRepository $chauffeurRepository,EntityManagerInterface $entityManagerInterface, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('profile_delete', $csrfToken))) {
            return new Response('Token CSRF invalide', Response::HTTP_FORBIDDEN);
        }
        
        $id = $chauffeur->getId();
        $chauffeur = $chauffeurRepository->find($id);
        $entityManagerInterface->remove($chauffeur);
        $entityManagerInterface->flush();

        return $this->redirectToRoute('app_chauffeur');
    }
    #[Route('/chauffeur/profile/{id}/creerPlanning', name: 'app_chauffeur_planning_create', methods: ['POST'])]
    public function createPlanning(Chauffeur $chauffeur,EntityManagerInterface $entityManagerInterface, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('planning_create', $csrfToken))) {
            return new Response('Token CSRF invalide', Response::HTTP_FORBIDDEN);
        }
        if ($chauffeur->getEvenements()!== null) {
            $this->addFlash('danger', 'Vous avez déjà un planning');
            return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
        }else{

        // $planning = new Planning();
        // $planning->setChauffeur($chauffeur);

        // $this->addFlash('success', 'Votre planning a bien été créé');
        // $entityManagerInterface->persist($planning);
        // $entityManagerInterface->flush();
            }

      return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
    }
    #[Route('/chauffeur/profile/{id}/ajouterVehicule', name: 'app_chauffeur_add_vehicule', methods: ['POST'])]
    public function addVehicule(Chauffeur $chauffeur,EntityManagerInterface $entityManagerInterface, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('vehicule_add', $csrfToken))) {
            return new Response('Token CSRF invalide', Response::HTTP_FORBIDDEN);
        }
       $nom = filter_var($request->request->get('nom'),  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
       $categorie = filter_var($request->request->get('categorie'),  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
       $nbPlace = filter_var($request->request->get('nbPlace'),  FILTER_VALIDATE_INT);
    
        if(!$nom || !$categorie || !$nbPlace){
            $this->addFlash('danger', 'Veuillez remplir tous les champs');
            return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
        }else{
            $vehicule = new Vehicule();
            $vehicule->setNom($nom);
            $vehicule->setCategorie($categorie);
            $vehicule->setNbPlace($nbPlace);
            $vehicule->setChauffeur($chauffeur);
            $entityManagerInterface->persist($vehicule);
            $entityManagerInterface->flush();
            $this->addFlash('success', 'Votre véhicule a bien été ajouté');
            return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
        }
    }
    #[Route('/chauffeur/profile/{id}/supprimerVehicule', name: 'app_chauffeur_delete_vehicule', methods: ['POST'])]
    public function deleteVehicule(Chauffeur $chauffeur, EntityManagerInterface $entityManager, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
    $csrfToken = $request->request->get('_csrf_token');
    if (!$csrfTokenManager->isTokenValid(new CsrfToken('vehicule_delete', $csrfToken))) {
        return new Response('Token CSRF invalide', Response::HTTP_FORBIDDEN);
    }

    $vehiculeId = $request->request->get('vehicule_id');
    if (!$vehiculeId) {
        return new Response('ID du véhicule non fourni', Response::HTTP_BAD_REQUEST);
    }

    // Trouver le véhicule dans la base de données et verifier si c'est bien le sien
    $vehicule = $entityManager->getRepository(Vehicule::class)->find($vehiculeId);
    if (!$vehicule || $vehicule->getChauffeur() !== $chauffeur) {
        return new Response('Véhicule non trouvé ou n’appartient pas à ce chauffeur', Response::HTTP_NOT_FOUND);
    }

    $entityManager->remove($vehicule);
    $entityManager->flush();


    $this->addFlash('success', 'Le véhicule a été supprimé avec succès.');
    return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
    }

}