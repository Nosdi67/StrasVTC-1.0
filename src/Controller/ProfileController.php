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
use FontLib\Table\Type\head;
use Knp\Component\Pager\PaginatorInterface;
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
    public function index(Security $security, ChauffeurRepository $chauffeurRepository,CourseRepository $courseRepository, PaginatorInterface $paginatorInterface,Request $request): Response
    {
        $user = $security->getUser();
        $chauffeurs = $chauffeurRepository->findAll();
    
        // Vérification du type de l'utilisateur
        if (!$user instanceof Utilisateur) {
            throw new \LogicException('L\'utilisateur n\'est pas correctement défini.');
        }
            $coursesAvenirQuery = $courseRepository->findCoursesAVenir($user);
            $coursesTermineesQuery = $courseRepository->findCoursesTerminees($user);
        
         // Paginer les résultats pour les courses à venir
            $coursesAvenir = $paginatorInterface->paginate(
            $coursesAvenirQuery, // La requête pour les courses à venir
            $request->query->getInt('coursesAvenir', 1), // definit l'url de la pagination
            5, // Limite à 5 par page
            [//donner un nom a la pagination, pour eviter qu'au changement de la page, ca affecte les deux pagination
                'pageParameterName' => 'coursesAvenir',
                // 'pageName'=> 'coursesAvenir',
                'distinct' => true
                ]
            );
            // dd($coursesAvenir);          
            $coursesTerminees = $paginatorInterface->paginate(
            $coursesTermineesQuery, 
            $request->query->getInt('coursesTerminees', 1), 
            5, 
                [
                'pageParameterName' => 'coursesTerminees',
                // 'pageName' => 'coursesTerminees',
                'distinct' => true
                ]
            );
            // dd($termineesPaginationsData);
            // Génération du rendu HTML pour la pagination
            $paginationAvenirHtml = $this->renderView('pagination_custom.html.twig', [
                // getPaginationData() renvoie les données de pagination, ca permet d'avoir acces aux touches suivant ou precedent par ex.
                'pagination' => $coursesAvenir->getPaginationData(),
                'pageParameterName' => 'coursesAvenir'
            ]);
            // dd($paginationAvenirHtml);
            
            $paginationTermineesHtml = $this->renderView('pagination_custom.html.twig', [
                'pagination' => $coursesTerminees->getPaginationData(),
                'pageParameterName' => 'coursesTerminees'
            ]);

        // Créer un tableau pour stocker les formulaires pour chaque course
        $avisForms = [];
    
        // Créer un formulaire distinct pour chaque course
        foreach ($coursesTerminees as $course) {
            $avisForm = $this->createForm(AvisFormType::class);
            $avisForms[$course->getId()] = $avisForm->createView(); // Stocker la vue du formulaire dans le tableau avec l'id de chaque course
        }
    
        return $this->render('profile/profile.html.twig', [
            'user' => $user,
            'coursesAvenir' => $coursesAvenir,
            'coursesTerminees' => $coursesTerminees,
            'chauffeurs' => $chauffeurs,
            'avisForms' => $avisForms,
            'paginationAvenirHtml' => $paginationAvenirHtml,
            'paginationTermineesHtml' => $paginationTermineesHtml,
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
        // dd($csrfTokenManager->isTokenValid($csrfToken));
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('profile_edit',$csrfToken))) {
            throw $this->createAccessDeniedException('CSRF token is invalid.');
        }

        $nom = filter_var($request->request->get('nom'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $prenom = filter_var($request->request->get('prenom'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL);
        $dateNaissance = filter_var($request->request->get('dateNaissance'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $sexe = htmlspecialchars($request->request->get('sexe'), ENT_QUOTES, 'UTF-8');
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
        $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
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
        $allowedMimeTypes = ['image/jpeg','image/jpg','image/gif','image/webp',];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return new Response('Type de fichier non supporté', Response::HTTP_BAD_REQUEST);
        }

        if($file->getSize() > 4194304) { // 4MB
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
        // Vérification des données
        if (!$oldPassword || !$newPassword || !$confirmNewPassword) {
            return new Response('Tous les champs sont obligatoires', Response::HTTP_BAD_REQUEST);
        }
        // si les mots de passe ne correspondent pas ou si l'ancien mot de passe est incorrect
        if (!password_verify($oldPassword, $actuallPassword) || $newPassword !== $confirmNewPassword) {
            return new Response('Ancien mot de passe incorrect, ou le nouveau mot de passe ne correspond pas à la confirmation', Response::HTTP_BAD_REQUEST);
        }

        $newHashPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $utilisateur->setPassword($newHashPassword);
        $entityManager->flush();
        $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
        return $this->redirectToRoute('app_profile');
    }
}
