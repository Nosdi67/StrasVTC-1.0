<?php

namespace App\Controller;

use DateTime;
use App\Entity\Vehicule;
use App\Entity\Chauffeur;
use App\Entity\Evenement;
use App\Form\SocieteType;
use App\Form\VehiculeType;
use App\Entity\Utilisateur;
use App\Form\ChauffeurType;
use App\Form\EventFormType;
use Doctrine\ORM\EntityManager;
use App\Repository\SocieteRepository;
use App\Repository\ChauffeurRepository;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChauffeurController extends AbstractController
{
    #[Route('/StrasVTC/chauffeur', name: 'app_chauffeur')]
    public function index(ChauffeurRepository $chauffeurRepository, SocieteRepository $societeRepository): Response
    {
        $chauffeurs = $chauffeurRepository->findAll();
        $societes = $societeRepository->findAll();
        $chauffeurForm = $this->createForm(ChauffeurType::class);
        return $this->render('chauffeur/index.html.twig', [
            'chauffeurs' => $chauffeurs,
            'societes' => $societes,
            'chauffeurForm' => $chauffeurForm->createView(),
        ]);
    }
   
    
    #[Route('/StrasVTC/chauffeur/profile/', name: 'app_chauffeur_profil')]
    public function chauffeurProfil(Security $security, ChauffeurRepository $chauffeurRepository, EvenementRepository $evenementRepository, SocieteRepository $societeRepository): Response
    {
        $user = $security->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $chauffeur = $chauffeurRepository->findOneBy(['utilisateur' => $user]);
        if (!$chauffeur) {
            throw $this->createNotFoundException('Profil de chauffeur non trouvé.');
        }

        $societe = $societeRepository->find($chauffeur->getSociete());
        $events = $evenementRepository->findBy(['chauffeur' => $chauffeur]);

        $addForm = $this->createForm(EventFormType::class, new Evenement());
        $editForm = $this->createForm(EventFormType::class);
        $deleteForm = $this->createForm(EventFormType::class);
        $addVehiculeForm = $this->createForm(VehiculeType::class);

        return $this->render('chauffeur/profileChauffeur.html.twig', [
            'chauffeur' => $chauffeur,
            'events' => $events,
            'societe' => $societe,
            'addForm' => $addForm->createView(),
            'editForm' => $editForm->createView(),
            'deleteForm' => $deleteForm->createView(),
            'addVehiculeForm' => $addVehiculeForm->createView()
        ]);
    }

    #[Route('/StrasVTC/chauffeur/profile/{id}', name: 'app_chauffeur_info')]
    public function info(Chauffeur $chauffeur, ChauffeurRepository $chauffeurRepository,EvenementRepository $evenementRepository,SocieteRepository $societeRepository): Response
    {
        $id = $chauffeur->getId();
        $chauffeur = $chauffeurRepository->find($id);
        $societe = $societeRepository->find($chauffeur->getSociete());
        $events = $evenementRepository -> findAll($chauffeur);
        $addForm = $this->createForm(EventFormType::class, new Evenement());
        $editForm =$this -> createForm(EventFormType::class);
        $deleteForm = $this->createForm(EventFormType::class);
        $addVehiculeForm = $this->createForm(VehiculeType::class);
    
        
        
        
        return $this->render('chauffeur/profileChauffeur.html.twig', [
            'chauffeur'=> $chauffeur,
            'events' => $events,
            'societe' => $societe,
            'addForm' => $addForm->createView(),
            'editForm' => $editForm -> createView(),
            'deleteForm' => $deleteForm -> createView(),
            'addVehiculeForm' => $addVehiculeForm -> createView()
            
        ]);
    }
    #[Route('/StrasVTC/chauffeur/profile/{id}/edit', name: 'app_chauffeur_profile_edit', methods: ['POST'])]
    public function profile(Chauffeur $chauffeur, ChauffeurRepository $chauffeurRepository,EntityManagerInterface $entityManagerInterface, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('profile_edit', $csrfToken))) {
            throw $this->createAccessDeniedException('CSRF token is invalid.');
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
        $chauffeur->getUtilisateur()->setEmail($email);
        $chauffeur->setDateNaissance(new \DateTime($dateNaissance));
        $chauffeur->setSexe($sexe);

        // Sauvegarder les modifications
        $entityManagerInterface->persist($chauffeur);
        $entityManagerInterface->flush();
    
        return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
    }
    #[Route('/StrasVTC/chauffeur/profile/{id}/editProfilePIcture', name: 'app_chauffeur_profile_edit_picture', methods: ['POST'])]
    public function editProfilePicture(Chauffeur $chauffeur, ChauffeurRepository $chauffeurRepository,EntityManagerInterface $entityManagerInterface, Request $request, SluggerInterface $slugger, CsrfTokenManagerInterface $csrfTokenManager): Response    
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('profile_image_change', $csrfToken))) {
            throw $this->createAccessDeniedException('CSRF token is invalid.');
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
    #[Route('/StrasVTC/chauffeur/profile/{id}/delete', name: 'app_chauffeur_profile_delete', methods: ['POST'])]
    public function delete(Chauffeur $chauffeur, ChauffeurRepository $chauffeurRepository,EntityManagerInterface $entityManagerInterface, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('profile_delete', $csrfToken))) {
            throw $this->createAccessDeniedException('CSRF token is invalid.');
        }
        
        $id = $chauffeur->getId();
        $chauffeur = $chauffeurRepository->find($id);
        $entityManagerInterface->remove($chauffeur);
        $entityManagerInterface->flush();

        return $this->redirectToRoute('app_chauffeur');
    }
    #[Route('/StrasVTC/chauffeur/profile/{id}/ajouterVehicule', name: 'app_chauffeur_add_vehicule', methods: ['POST'])]
    public function addVehicule(Vehicule $vehicule = null,Chauffeur $chauffeur,EntityManagerInterface $entityManagerInterface, Request $request,SluggerInterface $slugger): Response
    {
        $addVehiculeForm = $this->createForm(VehiculeType::class);
        $addVehiculeForm->handleRequest($request);

        if ($addVehiculeForm->isSubmitted() && $addVehiculeForm->isValid()) {
            $vehicule = new Vehicule();
            $vehicule->setMarque($addVehiculeForm->get('marque')->getData());
            $vehicule->setModele($addVehiculeForm->get('modele')->getData());
            $vehicule->setCategorie($addVehiculeForm->get('categorie')->getData());
            $vehicule->setNbPlace($addVehiculeForm->get('nbPlace')->getData());
            $vehicule->setChauffeur($chauffeur);

            $file = $addVehiculeForm->get('image')->getData();
            
            if ($file) {
                // Vérification de l'extension et du type MIME
                $allowedMimeTypes = ['image/jpeg','image/jpg','image/gif','image/webp',];
                if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('danger', 'Type de fichier non supporté');
                    return new Response('Type de fichier non supporté', Response::HTTP_BAD_REQUEST);
                }

                if ($file->getSize() > 4194304) { // 4MB
                    $this->addFlash('danger', 'La taille de l\'image ne doit pas dépasser 10Mo');
                    return new Response('Le fichier est trop volumineux', Response::HTTP_BAD_REQUEST);
                }

                $uploadDir = $this->getParameter('vehicule_directory');
                //orignialfilename recupere le nom orignial du fichier,tel qu'il etait sur son ordi
                //le filtre PATHINFO_FILENAME permet de recuperer le nom du fichier sans l'extension
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // le slugger permet de transformer le nom du fichier en un nom de fichier sécurisé avec des tirets et des underscores
                $safeFilename = $slugger->slug($originalFilename);
                // le uniqid() permet de generer un identifiant unique pour le nom du fichier
                // le guessExtension() permet de recuperer l'extension du fichier
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                try {
                    // on deplace le fichier dans le dossier de destination si tout va bien
                    $file->move($uploadDir, $newFilename);
                } catch (FileException $e) {
                    // sinon erreur
                    return new Response('Erreur lors du téléchargement du fichier', Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $vehicule->setImage($newFilename);
            }
            $entityManagerInterface->persist($vehicule);
            $entityManagerInterface->flush();
            $this->addFlash('success', 'Véhicule ajouté avec succès');
            return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
        }
    }
    #[Route('/StrasVTC/chauffeur/profile/{id}/modifierVehicule', name: 'app_chauffeur_edit_vehicule', methods: ['POST'])]
    public function editVehicule(Chauffeur $chauffeur, EntityManagerInterface $entityManager, Request $request, CsrfTokenManagerInterface $csrfTokenManager,SluggerInterface $slugger): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('vehicule_edit', $csrfToken))) {
            throw $this->createAccessDeniedException('CSRF token is invalid.');
        }
        $vehiculeId = $request->request->get('vehicule_id');
        $vehicule = $entityManager->getRepository(Vehicule::class)->find($vehiculeId);
        if (!$vehicule) {
            return new Response('Véhicule non trouvé', Response::HTTP_NOT_FOUND);
        }
        $vehicule->setNom($request->request->get('nom'));
        $vehicule->setMarque($request->request->get('marque'));
        $vehicule->setCategorie($request->request->get('categorie'));
        $vehicule->setNbPlace($request->request->get('nbPlace'));
        
        $imageFile = $request->files->get('image');
         if ($imageFile) {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

        try {
            $imageFile->move(
                $this->getParameter('vehicule_directory'), // Assurez-vous que ce paramètre est configuré
                $newFilename
            );
            $vehicule->setImage($newFilename);

        } catch (FileException $e) {
            return new Response('Erreur lors du téléchargement de l\'image', Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->addFlash('danger', 'Erreur lors du téléchargement de l\'image');
        }
    }
        $entityManager->flush();
        $this->addFlash('success', 'Véhicule modifié avec succès');
        return $this->redirectToRoute('app_chauffeur_info', ['id' => $chauffeur->getId()]);
    }
    #[Route('/StrasVTC/chauffeur/profile/{id}/supprimerVehicule', name: 'app_chauffeur_delete_vehicule', methods: ['POST'])]
    public function deleteVehicule(Chauffeur $chauffeur, EntityManagerInterface $entityManager, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
    $csrfToken = $request->request->get('_csrf_token');
    if (!$csrfTokenManager->isTokenValid(new CsrfToken('vehicule_delete', $csrfToken))) {
        throw $this->createAccessDeniedException('CSRF token is invalid.');
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