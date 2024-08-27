<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlanningController extends AbstractController
{
    #[Route('/planning', name: 'app_planning')]
    public function calendar(): Response
    {
        return $this->render('chauffeur/calendar.html.twig');
    }

}
