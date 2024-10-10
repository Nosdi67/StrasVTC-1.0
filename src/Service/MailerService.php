<?php
namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Twig\Environment;

class MailerService
{
    private $mailer;
    private $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendCourseConfirmationForUser($utilisateur, $course)
    {
        $email = (new TemplatedEmail())
            ->from('contact@exemple.com')
            ->to($utilisateur->getEmail())
            ->subject('Confirmation de création de votre course')
            ->htmlTemplate('mailer/confirmation_course.html.twig')
            ->context([
                'utilisateur' => $utilisateur,
                'course' => $course,
            ]);

        $this->mailer->send($email);
    }
    public function sendCourseConfirmationForChauffeur($chauffeur,$utilisateur, $course)
    {
        $email = (new TemplatedEmail())
            ->from('contact@exemple.com')
            ->to($chauffeur->getUtilisateur()->getEmail())
            ->subject('Confirmation de création de votre course')
            ->htmlTemplate('mailer/confirmation_course_chauffeur.html.twig')
            ->context([
                'chauffeur' => $chauffeur,
                'course' => $course,
                'utilisateur' => $utilisateur
            ]);
        $this->mailer->send($email);
    }
}