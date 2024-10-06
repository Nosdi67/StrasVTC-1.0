<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Chauffeur;
use App\Entity\Course;
use App\Form\AvisFormType;
use App\Entity\Utilisateur;
use App\Repository\AvisRepository;
use App\Repository\ChauffeurRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ProfileController extends AbstractController
{
    #[Route('/StrasVTC/profile', name: 'app_profile')]
    public function index(Security $security, ChauffeurRepository $chauffeurRepository,CourseRepository $courseRepository): Response
    {
        $user = $security->getUser();
        $chauffeurs = $chauffeurRepository->findAll();
    
        // Vérification du type de l'utilisateur
        if (!$user instanceof Utilisateur) {
            throw new \LogicException('L\'utilisateur n\'est pas correctement défini.');
        }
            $coursesAvenir = $courseRepository->findCoursesAVenir($user);
            $coursesTerminees = $courseRepository->findCoursesTerminees($user);
    
        // Créer un tableau pour stocker les formulaires pour chaque course
        $avisForms = [];
    
        // Créer un formulaire distinct pour chaque course
        foreach ($coursesTerminees as $course) {
            $avisForm = $this->createForm(AvisFormType::class);
            $avisForms[$course->getId()] = $avisForm->createView(); // Stocker la vue du formulaire dans le tableau
        }
    
        return $this->render('profile/profile.html.twig', [
            'user' => $user,
            'coursesAvenir' => $coursesAvenir,
            'coursesTerminees' => $coursesTerminees,
            'chauffeurs' => $chauffeurs,
            'avisForms' => $avisForms,
        ]);
    }
    
    #[Route('/StrasVTC/Course/{id}/avis', name: 'app_course_avis')]
    public function avis(Avis $avis=null,Chauffeur $chauffeur,Request $request, EntityManagerInterface $em, AvisRepository $avisRepository): Response
    {
        
        $user = $this->getUser();
        $userId = $user->getId();
        $chauffeurId = $chauffeur->getId();
        $data = $request->request->all();
        $courseId = $data['course_id'];
        // dd($dateAvis);
        $course = $em->getRepository(Course::class)->find($courseId);
        
        $existingAvis = $avisRepository->findExistingAvis($userId, $chauffeurId, $courseId);
        // dd($existingAvis);
    
        if ($existingAvis) {
            // Si un avis existe déjà, ajouter un message d'erreur et rediriger
            $this->addFlash('danger', 'Vous avez déjà noté cette course, vous ne pouvez pas en ajouter un autre avis.');
            return $this->redirectToRoute('app_profile');
        }
    
        $avis = new Avis();
        $form = $this->createForm(AvisFormType::class, $avis);
        $form->handleRequest($request);
        // dd($form);
        // dd($form->isValid());
        if ($form->isSubmitted() && $form->isValid()) {
            $avis->setUtilisateur($user);
            $avis->setChauffeur($chauffeur);
            $avis->setCourse($course);
            $avis->setDateAvis(new \DateTime());
            $em->persist($avis);
            $em->flush();

            $this->addFlash('success', 'Votre avis a été ajouté avec succès.');
        }
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/StrasVTC/profile/{id}/edit', name: 'app_profile_edit', methods: ['POST'])]
    public function edit(EntityManagerInterface $entityManager,Request $request,Utilisateur $utilisateur,CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        //Recuperation du token CSRF
        $csrfToken = new CsrfToken('profile_edit', $request->request->get('_csrf_token'));
        // Vérification du token CSRF,la methode isTokenValid() vérifie si le token CSRF est valide.
        //Si le token CSRF est invalide, la méthode renvoie false.
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('porfile_edit',$csrfToken))) {
            throw $this->createAccessDeniedException('CSRF token is invalid.');
        }

        $nom = filter_var($request->request->get('nom'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $prenom = filter_var($request->request->get('prenom'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL);
        $dateNaissance = filter_var($request->request->get('dateNaissance'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $sexe = filter_var($request->request->get('sexe'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        // Validation des données
        if (empty($nom) || empty($prenom) || empty($email) || empty($dateNaissance) || empty($sexe)) {
            return new Response('Tous les champs sont obligatoires', Response::HTTP_BAD_REQUEST);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response('Adresse email invalide', Response::HTTP_BAD_REQUEST);
        }
    
        $utilisateur->setNom($nom);
        $utilisateur->setPrenom($prenom);
        $utilisateur->setEmail($email);
        $utilisateur->setDateNaissance(new \DateTime($dateNaissance));
        $utilisateur->setSexe($sexe);
      
        $entityManager->flush();
    
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/StrasVTC/profile/{id}/changeProfileImg', name: 'app_profile_changeProfileImg', methods: ['POST'])]
    public function changeProfileImg( EntityManagerInterface $entityManager,Request $request,Utilisateur $utilisateur,SluggerInterface $slugger,CsrfTokenManagerInterface $csrfTokenManager): Response
     {
        $csrfToken = $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('profile_image_change', $csrfToken))) {
            return new Response('Token CSRF invalide', Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('profileImage');
        if (!$file) {
            return new Response('Aucun fichier téléchargé', Response::HTTP_BAD_REQUEST);
        }

        // Vérification de l'extension et du type MIME
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return new Response('Type de fichier non supporté', Response::HTTP_BAD_REQUEST);
        }

        if($file->getSize() > 10485760) { // 10MB
            return new Response('Le fichier est trop volumineux', Response::HTTP_BAD_REQUEST);
        }

        $uploadDir = $this->getParameter('profile_directory');// Récupération du chemin du répertoire de téléchargement
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);// Récupération du nom de fichier original
        $safeFilename = $slugger->slug($originalFilename);// Génération d'un nom de fichier sécurisé avec des slugs (remplacement des caractères spéciaux par des tirets)
        // Génération d'un nom de fichier unique en ajoutant un identifiant unique et l'extension du fichier
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($uploadDir, $newFilename); // Déplacement du fichier téléchargé vers le répertoire de téléchargement
        } catch (FileException $e) {// Gestion des erreurs de téléchargement
            return new Response('Erreur lors du téléchargement du fichier', Response::HTTP_INTERNAL_SERVER_ERROR);
            //retourne une réponse HTTP avec un code d'erreur 500 (Internal Server Error)
        }
        // Mise à jour du nom de fichier dans la base de données
        $utilisateur->setPhoto($newFilename);
        $entityManager->flush();

        return $this->redirectToRoute('app_profile', ['id' => $utilisateur->getId()]);
    }

    #[Route('/StrasVTC/profile/{id}/changePassword', name: 'app_profile_changePassword', methods: ['POST'])]
    public function changePassword(EntityManagerInterface $entityManager,Request $request,Utilisateur $utilisateur,CsrfTokenManagerInterface $csrfTokenManager): Response 
    {
        

        $oldPassword = $request->request->get('oldPassword');
        $newPassword = $request->request->get('newPassword');
        $confirmNewPassword = $request->request->get('confirmNewPassword');

        $actuallPassword = $utilisateur->getPassword();

        if (!$oldPassword || !$newPassword || !$confirmNewPassword) {
            return new Response('Tous les champs sont obligatoires', Response::HTTP_BAD_REQUEST);
        }

        if (!password_verify($oldPassword, $actuallPassword) || $newPassword !== $confirmNewPassword) {
            return new Response('Erreur dans la modification du mot de passe', Response::HTTP_BAD_REQUEST);
        }

        $newHashPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $utilisateur->setPassword($newHashPassword);
        $entityManager->flush();

        return $this->redirectToRoute('app_profile');
    }
}
