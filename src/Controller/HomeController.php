<?php

namespace App\Controller;

use App\Repository\ChauffeurRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/StrasVTC', name: 'app_home')]

    public function index(): Response
    {
        
        return $this->render('home/index.html.twig', [
            
            
        ]);
    }

    #[Route('/StrasVTC/Services', name: 'app_services')]
    public function services(): Response
    {
        return $this->render('home/services.html.twig', [

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
