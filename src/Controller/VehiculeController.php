<?php

namespace App\Controller;

use App\Entity\Vehicule;
use App\Form\VehiculeType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class VehiculeController extends AbstractController
{
    #[Route('/StrasVTC/vehicule', name: 'app_vehicule')]
    public function index(Request $request, Vehicule $vehicule = null): Response
    {
        $vehiculeForm = $this->createForm(VehiculeType::class);
        $vehiculeForm->handleRequest($request);

        if ($vehiculeForm->isSubmitted() && $vehiculeForm->isValid()) {
            $data = $vehiculeForm->getData();
            $vehicule = new Vehicule();
            $vehicule->setNom($data['nom']);
            $vehicule->setCategorie($data['categorie']);
            $vehicule->setNbPlace($data['nbPlace']);
            $vehicule->setChauffeur($data['chauffeur']);

            return $this->redirectToRoute('app_chauffeur_info', ['id' => $data['chauffeur']]);
        }
            return $this->render('home/debug.html.twig', [
            'vehiculeForm' => $vehiculeForm->createView(),
            ]);
    }
}
