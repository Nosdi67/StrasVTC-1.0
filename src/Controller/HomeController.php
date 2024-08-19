<?php

namespace App\Controller;

use App\Entity\Chauffeur;
use App\Entity\Utilisateur;
use App\Repository\ChauffeurRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;

class HomeController extends AbstractController
{
    #[Route('/StrasVTC', name: 'app_home')]

    public function index(): Response
    {
        
        return $this->render('home/index.html.twig', [
            
            
        ]);
    }

    #[Route('/StrasVTC/ListeChauffeur', name: 'app_listChauffeur')]
    public function listCourse(ChauffeurRepository $chauffeurRepository,Security $security): Response
    {
        // $isAuth=$security->isGranted('IS_AUTHENTICATED_FULLY');
        $chauffeurs = $chauffeurRepository->findAll();
        return $this->render('home/listeChauffeur.html.twig', [
            'chauffeurs' => $chauffeurs,
            // 'isAuth'=>$isAuth,
        ]);
    }
}
