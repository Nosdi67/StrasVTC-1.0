<?php

namespace App\Controller;

use App\Entity\Chauffeur;
use App\Entity\Course;
use App\Form\CourseType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Json;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CourseController extends AbstractController
{
    #[Route('/StrasVTC/course/{id}', name: 'app_new_course')]
    public function index( Course $course = null, Request $request,Chauffeur $chauffeur, EntityManagerInterface $entityManagerInterface): Response
    {
        $course = new Course();
        $course->setChauffeur($chauffeur);
        $courseForm = $this->createForm(CourseType::class, $course);
        $courseForm->handleRequest($request);
    
        if ($courseForm->isSubmitted() && $courseForm->isValid()) {
            $entityManagerInterface->persist($course);
            $entityManagerInterface->flush();
    
            
            return $this->redirectToRoute('app_confirmationCourse', [
                'id' => $course->getId(),
            ]);
        }
    
        return $this->render('course/index.html.twig', [
            'controller_name' => 'CourseController',
            'courseForm' => $courseForm,
        ]);
    }

    public function calculateRoute($startLat, $startLng, $endLat, $endLng){
        $apiKey = "5b3ce3597851110001cf6248877405c36c474b1a92ca3d006b4f4cfb";
        $url = "https://api.openrouteservice.org/v2/directions/driving-car?api_key=$apiKey&start=$startLng,$startLat&end=$endLng,$endLat";

        $ch = curl_init(); //initalisation d'une session curl
        curl_setopt($ch, CURLOPT_URL, $url);//definition de l'url d'api
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//retourne le resultat de la requete sous forme d'un string lol
        $response = curl_exec($ch);//execution de la requete curl
        curl_close($ch);//fermeture de la session curl

        $apiData = json_decode($response, true);//decodage de la reponse json

        if($apiData > 0 && isset($apiData["features]"])){

        }
    }
    // #[Route('/StrasVTC/getAdresses', name: 'getAdresses',methods: ['POST'])] 
    // public function getAdresses(Request $request): JsonResponse
    // {
    //     $data = json_decode($request->getContent(), true);//decode the JSON data

    //     if ($data === null) {
    //         return new JsonResponse(['error' => 'Invalid JSON'], 400);
    //     }

    //     $departureAddress = $data['departureAddress'] ?? null;//recuperation des adresses
    //     $destinationAddress = $data['destinationAddress'] ?? null;

    //     if ($departureAddress === null || $destinationAddress === null) {// si la recupération des adresses est null 
    //         return new JsonResponse(['error' => 'Missing address data'], 400);// alors on renvoie une erreur 400
    //     }
    //     dump($departureAddress.$destinationAddress);
       
    //     error_log('Departure Address: ' . $departureAddress);// on log les adresses pour déboguer
    //     error_log('Destination Address: ' . $destinationAddress);

    //     // On retourne les adresses en format JSON
    //     return new JsonResponse([
    //         'departureAddress' => $departureAddress,
    //         'destinationAddress' => $destinationAddress,
    //     ]);
    




    #[Route('/StrasVTC/ConfirmationDeCourse/{id}', name: 'app_confirmationCourse')]
    public function confirmationCourse(Course $course): Response {
   
   
        return $this->render('course/validation.html.twig', [
        'controller_name' => 'CourseController',
        'course' => $course,
    ]);
    }
}
