<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Security\EmailVerifier;
use App\Form\RegistrationFormType;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\AppAuthentificatorAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager,SluggerInterface $slugger): Response
    {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
    
        // Vérification si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload de la photo de profil
            $file = $form->get('photo')->getData();
    
            if ($file) {
                // Récupération du répertoire de stockage des photos
                $uploadDir = $this->getParameter('profile_directory');
                // Traitement du nom de fichier
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
    
                // Vérification de la taille du fichier 
                if ($file->getSize() > 4194304) { // 4MO
                    // Message d'erreur si le fichier est trop volumineux
                    $this->addFlash('error', 'Le fichier est trop volumineux. Veuillez choisir un fichier de moins de 4 Mo.');
                    return $this->redirectToRoute('app_register');
                }
                try {
                    // Envoi du fichier dans le répertoire upload
                    $file->move($uploadDir, $newFilename);
                } catch (FileException $e) {
                    // Gestion des erreurs lors du téléchargement du fichier
                    $this->addFlash('error', 'Erreur lors du téléchargement du fichier. Veuillez réessayer.');
                    return $this->redirectToRoute('app_register');
                }
    
                // Mise à jour de l'utilisateur avec le nouveau nom de fichier
                $user->setPhoto($newFilename);
            }
            
    
            // Hashage du mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
    
            // Persistance de l'utilisateur dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();
    
            // Envoi d'un email de confirmation
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('strasvts.sup@gmail.com', 'Stras Admin'))
                    ->to($user->getEmail())
                    ->subject('Veuillez confirmer votre adresse email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
    
            try {
                // Connexion automatique de l'utilisateur
                return $security->login($user, AppAuthentificatorAuthenticator::class, 'main');
            } catch (\Exception $e) {
                // Message d'erreur en cas d'échec de la connexion automatique
                $this->addFlash('error', 'Erreur lors de la connexion automatique. Veuillez vous connecter manuellement.');
                return $this->redirectToRoute('app_login');
            }
    
            // Redirection vers la page d'accueil après l'inscription réussie
            return $this->redirectToRoute('app_home');
        }
    
        // Affichage du formulaire d'inscription
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register');
    }
}
