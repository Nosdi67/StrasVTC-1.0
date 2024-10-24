<?php

namespace App\Controller;

use App\Entity\Chauffeur;
use App\Form\SocieteType;
use App\Entity\Utilisateur;
use App\Form\ChauffeurType;
use App\Repository\CourseRepository;
use App\Repository\SocieteRepository;
use App\Repository\ChauffeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminController extends AbstractController
{
    #[Route('/StrasVTC/admin', name: 'app_admin')]
    public function index(ChauffeurRepository $chauffeurRepository, SocieteRepository $societeRepository): Response
    {
        $user = $this->getUser();
        if(!$user->getRoles() == ['ROLE_ADMIN']) {
            $this ->addFlash('danger', 'Vous n\'avez pas les droits pour accéder à cette page');
            return $this->redirectToRoute('app_home');
        }
        $chauffeurForm = $this->createForm(ChauffeurType::class);
        $chauffeurForm->createView();
        $societeForm=$this->createForm(SocieteType::class);
        $societeForm->createView();
        $chauffeurs = $chauffeurRepository->findAll();
        $societes = $societeRepository->findAll();
        


        return $this->render('admin/index.html.twig', [
            'chauffeurForm' => $chauffeurForm,
            'societeForm'=>$societeForm,
            'chauffeurs' => $chauffeurs,
            'societes' => $societes,
            'user' => $user
        ]);
    }
    #[Route('/StrasVTC/admin/addChauffeur', name: 'app_chauffeur_add')]
    public function add(Request $request,Utilisateur $utilisateur=null,Chauffeur $chauffeur=null, EntityManagerInterface $em, SluggerInterface $slugger, SocieteRepository $societeRepository,UserPasswordHasherInterface $passwordHasher): Response
    {
        $utilisateur=new Utilisateur();
        $chauffeur = new Chauffeur();
        $chauffeur ->setUtilisateur($utilisateur);
        $form = $this->createForm(ChauffeurType::class,$chauffeur );
        $form->handleRequest($request);
        // dd($form);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données du formulaire
            $data = $request->request->all();
            $files = $request->files->all();
            
            $nom = $data['chauffeur']['nom'];
            $prenom = $data['chauffeur']['prenom'];
            $email = filter_var($request->request->get('email'), FILTER_SANITIZE_EMAIL);
            $societeId = $data['chauffeur']['societe'];
            $dateNaissance = $data['chauffeur']['dateNaissance'];
            $sexe = $data['chauffeur']['sexe'];
            $image = $files['chauffeur']['image']; // Récupérer l'image depuis les fichiers
            $societe = $societeRepository->find($societeId);
            
            // dd($societe);
            // Vérification que tous les champs requis sont remplis
            if (!$nom || !$prenom || !$email || !$dateNaissance || !$sexe || !$societe || !$image) {
                $this->addFlash('danger', 'Tous les champs sont obligatoires.');
                return $this->redirectToRoute('app_chauffeur_add'); // Redirection si un champ manque
            }

            // Gestion de l'image
            if ($image) {
                $uploadDir = $this->getParameter('profile_directory');
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();
                
                // Vérification de la taille de l'image (10Mo max)
                if ($image->getSize() > 4194304) {
                    $this->addFlash('danger', 'La taille de l\'image ne doit pas dépasser 10Mo');
                    return $this->redirectToRoute('app_chauffeur_add');
                }
                
                // Vérification du type MIME de l'image
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
                if (!in_array($image->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('danger', 'Type de fichier non supporté');
                    return $this->redirectToRoute('app_chauffeur_add');
                }

                // Tentative de déplacement du fichier
                try {
                    $image->move($uploadDir, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors du téléchargement du fichier');
                    return $this->redirectToRoute('app_chauffeur_add');
                }
            }
           
            $utilisateur->setNom($nom);
            $utilisateur->setPrenom($prenom);
            $utilisateur->setSexe($sexe);
            $utilisateur->setDateNaissance(new \DateTime($dateNaissance));
            $utilisateur->setEmail($email);
            $utilisateur->setRoles(['ROLE_CHAUFFEUR']);
            

             // Traitement du mot de passe
             $password = $form->get('password')->getData();
             $passwordConfirmation = $form->get('passwordConfirmation')->getData();
             
 
             if ($password && $passwordConfirmation) {
                 if ($password !== $passwordConfirmation) {
                     $this->addFlash('danger', 'Les mots de passe ne correspondent pas');
                     return $this->redirectToRoute('app_chauffeur_add');
                 }
 
                
                 $hashedPassword = $passwordHasher->hashPassword($utilisateur, $password);
                 $utilisateur->setPassword($hashedPassword);
             }
            // Création et persistance du nouvel objet Chauffeur
            $chauffeur->setNom($nom);
            $chauffeur->setPrenom($prenom);
            $chauffeur->setDateNaissance(new \DateTime($dateNaissance));
            $chauffeur->setSexe($sexe);
            $chauffeur->setSociete($societe);
            $chauffeur->setImage($newFilename);

            $em->persist($utilisateur);
            $em->persist($chauffeur);
            $em->flush();

            // Message de succès et redirection
            $this->addFlash('success', 'Chauffeur ajouté avec succès');
            return $this->redirectToRoute('app_chauffeur');
        }

        // Si le formulaire n'est pas valide, rediriger avec les erreurs
        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('danger', $error->getMessage());
        }

        return $this->redirectToRoute('app_admin');
    }
    #[Route('/StrasVTC/admin/addSociete', name: 'app_societe_add')]
    public function addSociete(Request $request, EntityManagerInterface $em): Response
    {
       $form = $this->createForm(SocieteType::class);
       $form->handleRequest($request);
       if($form->isSubmitted() && $form->isValid()){
        $societe = $form->getData();
            $em->persist($societe);
            $em->flush();
            $this->addFlash('success', 'Société ajoutée avec succès');
            return $this->redirectToRoute('app_admin');
        }
    }
   
    #[Route('/StrasVTC/admin/searchCourse/{parameter}', name: 'app_admin_search_course')]
    public function searchCourse(string $parameter, Request $request, CourseRepository $courseRepository): Response
    {
        // dd($request);
        $searchTerm = $request->query->get('parameter-'.$parameter);
        // dd($searchTerm);
        // dans cette variable je rajoute la requete de base de Doctrine pour chercher les courses par parametre
        $method = 'findAllCoursesBy' . ucfirst($parameter);
        // si la methode creer existe dans le repositry des courses
        if (method_exists($courseRepository, $method)) {
            // je passe le parametre de recherche a la methode creer dans le repositry des courses
            $courses = $courseRepository->$method($searchTerm);
            // dd($courses);
        } else {
            // sinon false
            throw new \InvalidArgumentException("Invalid search parameter: $parameter");
        }
        // dd($courses);
        return $this->render('admin/search_results.html.twig', [
            'courses' => $courses,
            'parameter' => $parameter,
        ]);
    }
}
