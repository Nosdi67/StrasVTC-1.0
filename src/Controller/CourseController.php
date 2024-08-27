<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\ChauffeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CourseController extends AbstractController
{
    #[Route('/StrasVTC/course/', name: 'app_new_course', methods: ['POST', 'GET'])]
    public function index(Course $course = null, Request $request, ChauffeurRepository $chauffeurRepository, EntityManagerInterface $entityManagerInterface, CsrfTokenManagerInterface $csrfTokenManager ): Response
    {      
        // $csrfToken = $request -> get('_csrf_token');
        // dump($csrfToken);die;
        // if (!$csrfTokenManager->isTokenValid(new CsrfToken('new_course', $csrfToken))) {
        //     return new Response('Token CSRF invalide', Response::HTTP_FORBIDDEN);
        // }

        $session = $request->getSession();
        $routeData = $session->get('route_data');
        $utilisateur = $this->getUser();
        $dateDepart = filter_var($request->request->get('dateDepart'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $vehicule = filter_var($request->request->get('vehicule'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nbPassager = filter_var($request->request->get('nbPassager'), FILTER_VALIDATE_INT);
        $chauffeurId = filter_var($request->request->get('chauffeur-select'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $chauffeur = $chauffeurRepository -> find($chauffeurId);
        $roundTarif = null;
        // dump($routeData); 
    
        if ($routeData) {
            $course = new Course();
            $chauffuers = $chauffeurRepository->findAll();
            $courseForm = $this->createForm(CourseType::class, $course);
            $courseForm->handleRequest($request);

            // Récupérer les coordonnées de départ et d'arrivée de la session
            $startLat = $routeData['startLat'];
            $startLng = $routeData['startLng'];
            $endLat = $routeData['endLat'];
            $endLng = $routeData['endLng'];

        
            
            $clientDistance = $routeData['clientDistance'] / 1000 ; 
            // $clientDistance = floatval($clientDistance);
           
    
            
            // Appeler la fonction calculateRoute pour recalculer la distance, durée, et tarif côté serveur
            $calculatedData = $this->calculateRoute($startLat, $startLng, $endLat, $endLng);
            // dump($calculatedData);
            
            // Comparer les valeurs calculées avec celles stockées en session
            $distanceDifference = abs($calculatedData['distance'] - $clientDistance);//abs =absolute value
            if($tarifDifference = abs(floatval($calculatedData['tarifTest']) - floatval($routeData['clientTarif']))){
                $roundTarif=round($calculatedData['tarifTest'], 1);
            };
            // dump($clientDistance, $calculatedData['distance']); 
            // Si les différences sont acceptables, continuer
            if ($distanceDifference <= 30) {
                
                
            } else {
                throw new \Exception('Les données calculées côté serveur ne correspondent pas aux données stockées en session.');
                // dump($calculatedData, $routeData);
            }
            
            if(isset($_POST["submit"])) {
                    $course->setAdresseDepart($routeData['startAddress']);
                    $course->setAdresseArivee($routeData['endAddress']);
                    $course->setDateDepart(new \DateTime($dateDepart));
                    $course-> setVehicule($vehicule);
                    $course -> setChauffeur($chauffeur);
                    $course -> setNbPassager($nbPassager);
                    $course -> setPrix($roundTarif);
                    $course -> setUtilisateur($utilisateur);

                    $entityManagerInterface->persist($course);
                    $entityManagerInterface->flush();
                    

                    return $this->redirectToRoute('app_confirmationCourse',[
                        'id' => $course->getId(),
                    ]);
                }
                    
                return $this->render('course/index.html.twig', [
                    'courseForm' => $courseForm,
                    'chauffeurs' => $chauffuers,
                    'startAddress' => $routeData['startAddress'],
                    'endAddress' => $routeData['endAddress'],
                    'tarif' => $roundTarif ,
                ]);
                
             }
                    
        throw $this->createNotFoundException('Les données de la route sont introuvables dans la session.');
    }

    #[Route ('store-route-data', name: 'store_route_data', methods: ['POST'])]
    public function storeRouteData(Request $request): Response    
    {
        
        
        $session = $request->getSession();

        $clientDistance = $request->request->get('clientDistance');
        $clientDuration = $request->request->get('clientDuration');
        $clientTarif = $request->request->get('clientTarif');
        $startLat = $request->request->get('startLat');
        $startLng = $request->request->get('startLng');
        $endLat = $request->request->get('endLat');
        $endLng = $request->request->get('endLng');
        $startAddress = $request->request->get('startAddress');
        $endAddress = $request->request->get('endAddress');

        $session->set('route_data',[
            'clientDistance' => $clientDistance,
            'clientDuration' => $clientDuration,
            'clientTarif' => $clientTarif,
            'startLat' => $startLat,
            'startLng' => $startLng,
            'endLat' => $endLat,
            'endLng' => $endLng,
            'startAddress' => $startAddress,
            'endAddress' => $endAddress,
        ]);

        return $this->redirectToRoute('app_new_course');
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
        $features = null;
        // dump($apiData);
            
        

        if (!isset($apiData['features']) || count($apiData['features']) === 0) {
            throw new \Exception('No features found in the API response');
        }
    
        $features = $apiData['features'][0]; // Initialisation de $features
    
        if(isset($apiData['bbox']) && count($apiData['bbox']) >= 4) { //si les coordonees sont presentes
            $startLat = $apiData['bbox'][1];//on les recupere ici
            $startLng = $apiData['bbox'][0];
            $endLat = $apiData['bbox'][3];
            $endLng = $apiData['bbox'][2];
        }else{
            throw new \Exception('No coordinates found');
        }
        if (isset($features['properties']['summary']['distance']) && isset($features['properties']['summary']['duration'])) {
            // Récupération de la distance et de la durée
            $distance = $features['properties']['summary']['distance'] / 1000;// Distance en kilomètres
            $duration = $features['properties']['summary']['duration'] / 60;// Durée en minutes
        } else {
            throw new \Exception('distance and duration data unavailable');
        }

        $tarifTest=$distance*0.5 ;

        return [// on retourne les donnees
            'distance' => $distance,
            'duration' => $duration,
            'startLat' => $startLat,
            'startLng' => $startLng,
            'endLat' => $endLat,
            'endLng' => $endLng,
            'tarifTest' => $tarifTest
        ];

        throw new \Exception('No route found');//si pas de reponse, renvoie une exception
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
