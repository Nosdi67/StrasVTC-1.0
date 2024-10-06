<?php

namespace App\Controller;


use App\Entity\Course;
use DateTimeImmutable;
use App\Form\CourseType;
use App\Entity\Chauffeur;
use App\Entity\Evenement;
use App\Entity\Utilisateur;
use App\Service\PdfService;
use App\Repository\AvisRepository;
use App\Repository\ChauffeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CourseController extends AbstractController
{
    #[Route('/StrasVTC/course/', name: 'app_new_course', methods: ['POST', 'GET'])]
    public function index(Course $course = null, Evenement $event = null, Request $request, ChauffeurRepository $chauffeurRepository, EntityManagerInterface $entityManagerInterface,AvisRepository $avisRepository): Response
    {      
    
    $session = $request->getSession();
    $routeData = $session->get('route_data');
    $chauffeurId = $session->get('chauffeurId');
    $utilisateur = $this->getUser();
    $courseForm = $this->createForm(CourseType::class, $course);
    $courseForm->handleRequest($request);
    // dd($courseForm);   
    
    
    $addressDepart = $courseForm->get('adresseDepart')->getData();
    $addressArrivee = $courseForm->get('adresseArivee')->getData();
    $dateString = $routeData['dateDepart'];
    $dateDepart = new DateTimeImmutable($dateString);
    // dd($dateString,$dateDepart);
    $nbPassager = $routeData['nbPassager'];
    $vehicule = $routeData['vehiculeType'];
    $chauffeur = $chauffeurRepository->find($chauffeurId);
    
    
    $roundTarif = null;
    
    if ($routeData) { 
        
        // Récupérer les coordonnées de départ et d'arrivée de la session
        $startLat = $routeData['startLat'];
        $startLng = $routeData['startLng'];
        $endLat = $routeData['endLat'];
        $endLng = $routeData['endLng'];
                  
        $clientDistance = floatval($routeData['clientDistance']) / 1000; // convertir string -> float/int
        
        // Appeler la fonction calculateRoute pour recalculer la distance, durée, et tarif côté serveur
        $calculatedData = $this->calculateRoute($startLat, $startLng, $endLat, $endLng);
        $duration = $calculatedData['duration'];
        
        // Comparer les valeurs calculées avec celles stockées en session
        $distanceDifference = abs($calculatedData['distance'] - $clientDistance); // abs = absolute value
        if($tarifDifference = abs(floatval($calculatedData['tarifTest']) - floatval($routeData['clientTarif']))) {
            $roundTarif = round($calculatedData['tarifTest'], 0);
        }
        
        // Si les différences sont acceptables, continuer
        if ($distanceDifference > 2.5) {
            throw new \Exception('Les données calculées côté serveur ne correspondent pas aux données stockées en session.');
        }

        if ($courseForm->isSubmitted() && $courseForm->isValid()) {
            
            // Calcul de la date de fin de la course
            $dateFin = (clone $dateDepart)->add(new \DateInterval('PT' . $duration . 'M')); // P = Period, T = Time, M = Minute

            // Calcul de la distance de retour
            $returnRouteData = $this->calculateRoute($endLat, $endLng, $startLat, $startLng);
            $returnDistance = $returnRouteData['distance'];

            // Calcul du temps de retour et ajout d'un buffer de sécurité
            $returnTime = ($returnDistance / 100) * 60; // Temps de retour en minutes
            $securityBuffer = $returnTime * 0.3; // Buffer de sécurité de 30% du temps de retour
            $totalTime = intval($returnTime + $securityBuffer); // Convertir en entier après avoir arrondi

            // Calcul de l'heure à laquelle le chauffeur sera de nouveau disponible
            // PT = Period, T = Time, M = Minute
            $actualAvailableTime = (clone $dateFin)->add(new \DateInterval('PT' . $totalTime . 'M'));   

            // Vérification de la disponibilité du chauffeur en tenant compte du temps de retour et du buffer
            if (!$chauffeurRepository->isChauffeurAvailable($chauffeur, $dateDepart, $actualAvailableTime)) {
                // Le chauffeur n'est pas disponible
                $this->addFlash('danger', 'Le chauffeur sélectionné est déjà pris pour cette période.');
                return $this->redirectToRoute('app_new_course');
            }

            // Création de la course et de l'événement associés
            $course = new Course();
            $course->setAdresseDepart($addressDepart);
            $course->setAdresseArivee($addressArrivee);
            $course->setDateDepart($dateDepart);
            $course->setDateFin($dateFin);
            $course->setVehicule($vehicule);
            $course->setChauffeur($chauffeur);
            $course->setNbPassager($nbPassager);
            $course->setPrix($roundTarif);
            $course->setUtilisateur($utilisateur);

            $event = new Evenement();
            $event->setChauffeur($chauffeur);
            $event->setTitre($addressDepart . ' - ' . $addressArrivee);
            $event->setDebut($dateDepart);
            $event->setFin($dateFin);

            // Enregistrer la course et l'événement dans la base de données
            $entityManagerInterface->persist($course);
            $entityManagerInterface->persist($event);
            $entityManagerInterface->flush();
                
            // Supprimer les données de route de la session après la création de la course
            $session->remove('route_data');
            $session->remove('chauffeurId');
            return $this->redirectToRoute('app_confirmationCourse', [
                'id' => $course->getId(),
            ]);
        }
        $allAvis = $avisRepository->findAll($chauffeur);
        shuffle($allAvis);
        $randomAvis = array_slice($allAvis, 0, 5);//afficher 5 avis random
        
        return $this->render('course/index.html.twig', [
            'courseForm' => $courseForm,
            'startAddress' => $routeData['startAddress'],
            'endAddress' => $routeData['endAddress'],
            'prix' => $roundTarif,
            'vehicule' => $vehicule,
            'nbPassager' => $nbPassager,
            'chauffeur' => $chauffeur,
            'randomAvis' => $randomAvis,
            'dateDepart' => $dateDepart,
        ]);
    }
                
    // Si les données de la route sont introuvables dans la session, lancer une exception
    throw $this->createNotFoundException('Les données de la route sont introuvables dans la session.');
}
    #[Route('store-chauffeur-choice', name: 'store_chauffeur_choice', methods: ['POST'])]
    public function storeChauffeurChoice(Request $request,CsrfTokenManagerInterface $csrfTokenManagerInterface): Response
    {
       
        $csrfToken = $request->request->get('_csrf_token');
        if(!$csrfTokenManagerInterface->isTokenValid(new CsrfToken('store_chauffeur_choice', $csrfToken))) {
            throw $this->createAccessDeniedException('CSRF token is invalid.');
        }
        $chauffeurId = $request->request->get('chauffeurId');
        $session = $request->getSession();
        $session->set('chauffeurId', $chauffeurId);
        return $this->redirectToRoute('app_new_course');
    }

    #[Route ('store-route-data', name: 'store_route_data', methods: ['POST'])]
    public function storeRouteData(Request $request,CsrfTokenManagerInterface $csrfTokenManagerInterface): Response    
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur) {// Vérifier si l'utilisateur est connecté
            throw new \Exception('Utilisateur non trouvé.');
            return $this->redirectToRoute('app_home');
        }
        if ($this->isGranted('ROLE_CHAUFFEUR', $utilisateur)) {// Vérifier si l'utilisateur est un chauffeur
            throw new \Exception('Un chauffeur ne peut pas creer de course avec son compte professionel.');
            return $this->redirectToRoute('app_home');
        }
        $csrfToken = $request->request->get('_csrf_token');
        if(!$csrfTokenManagerInterface->isTokenValid(new CsrfToken('store_route_data', $csrfToken))) {
            throw $this->createAccessDeniedException('CSRF token is invalid.');
        }
        $session = $request->getSession();
        $dateDepart = $request->request->get('date');
        $vehiculeType = $request->request->get('vehicle');
        $nbPassager = $request->request->get('passengers');
        $clientDistance = $request->request->get('clientDistance');
        $clientDuration = $request->request->get('clientDuration');
        $clientTarif = $request->request->get('clientTarif');
        $startLat = $request->request->get('startLat');
        $startLng = $request->request->get('startLng');
        $endLat = $request->request->get('endLat');
        $endLng = $request->request->get('endLng');
        $startAddress = $request->request->get('startAddress');
        $endAddress = $request->request->get('endAddress');
        // dd($request);

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
            'vehiculeType' => $vehiculeType,
            'nbPassager' => $nbPassager,
            'dateDepart' => $dateDepart,
        ]);
        // dd($session->get('route_data'));
        return $this->redirectToRoute('app_choix_chauffeur');
    }
    #[Route('/StrasVTC/course/Choix-Chauffeur', name: 'app_choix_chauffeur')]
    public function choixChauffeur(ChauffeurRepository $chauffeurRepository, Request $request): Response
    {
        $session = $request->getSession();//recuperation de la session
        $routeData = $session->get('route_data');//recuperation des donnees route_data stockees en session
        $dateString = $routeData['dateDepart'];//recuperation de la date de depart
        $dateDepart = new DateTimeImmutable($dateString);//conversion de la date de depart en objet DateTimeImmutable
        $vehiculeType = $routeData['vehiculeType'];//recuperation du type de vehicule
    
        // Calcul de la durée estimée de la course
        $clientDistance = $routeData['clientDistance'];// recuperation de la distance calculee coté client
        $duration = ($clientDistance / 100) * 60; // Estimation basée sur la distance
        $hours = floor($duration / 60);//calcul du nombre d'heures
        $minutes = $duration % 60;  // Calcul des minutes restantes

        // Calcul de la date de fin de la course
        // PT = period, H = hours, M = minutes
        // clone = copie de l'objet DateTimeImmutable
        $dateFin = (clone $dateDepart)->add(new \DateInterval("PT{$hours}H{$minutes}M"));
    
        // Calcul de la distance de retour 
        $returnDistance = $clientDistance;
    
        // Calcul du temps de retour et ajout d'un buffer de sécurité
        $returnTime = ($returnDistance / 100) * 60;
        $securityBuffer = $returnTime * 0.3;// buffer de securite de 30% du temps de retour
        $totalTime = intval($returnTime + $securityBuffer);// conversion en entier après avoir arrondi
    
        // Calcul de l'heure à laquelle le chauffeur sera de nouveau disponible
        // on clone de nouveau la date de fin, puis on ajoute un intervalle calculé precedemment
        $actualAvailableTime = (clone $dateFin)->add(new \DateInterval('PT' . $totalTime . 'M'));
        // via cette requette on trouve tous les chauffeurs disponibles pour la date de depart choisit
        $chauffeurs = $chauffeurRepository->findAvailableChauffeursByVehiculeType($vehiculeType,$dateDepart,$actualAvailableTime
        );
    
        $chauffeurNotes = [];// tableau pour stocker les notes des chauffeurs
        foreach ($chauffeurs as $chauffeur) {// boucle pour parcourir les chauffeurs
            //fonction qui recupere 5 avis random
            $chauffeurNotes[$chauffeur->getId()] = $this->calculateAverageRating($chauffeur);
        }
    
        return $this->render('course/choixChauffeur.html.twig', [
            'chauffeurs' => $chauffeurs,
            'chauffeurNotes' => $chauffeurNotes,
        ]);
    }
    public function calculateAverageRating(Chauffeur $chauffeur): ?float
    {
        $avis = $chauffeur->getAvis();
        $totalRating = 0;
        $count = count($avis);

        if ($count === 0) {
            return null;
        }

        foreach ($avis as $avis) {
            $totalRating += $avis->getNoteChauffeur();
        }

        return $totalRating / $count;
    }

    public function calculateRoute($startLat, $startLng, $endLat, $endLng){
        $apiKey = "5b3ce3597851110001cf6248877405c36c474b1a92ca3d006b4f4cfb";
        $url = "https://api.openrouteservice.org/v2/directions/driving-car?api_key=$apiKey&start=$startLng,$startLat&end=$endLng,$endLat";

        $ch = curl_init(); //initalisation d'une session curl
        curl_setopt($ch, CURLOPT_URL, $url);//definition de l'url d'api
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//retourne le resultat de la requete sous forme d'une chaine de caractere
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
            $duration = round($features['properties']['summary']['duration'] / 60);// Durée en minutes
            // dd($duration);
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
    #[Route('/StrasVTC/Course/{id}/devis', name: 'app_course_devis')]
    public function devis(Course $course,PdfService $pdfService): Response
    {
        $user = $this->getUser();

        if ($course->getUtilisateur() !== $user && $course->getChauffeur() !== $user) {
            // Si l'utilisateur n'est ni le client ni le chauffeur, une réponse 403, acces denile 
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à ce devis.');
        }

        $societe = $course->getChauffeur()->getSociete();
        $html=$this->renderView('devis/devis.html.twig', [
            'course' => $course,
            'societe' => $societe,
            'user' => $user,
        ]);
        $pdfContnet= $pdfService->generatePDF($html);
        return new Response($pdfContnet, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="devis.pdf"',
        ]);
    }

    #[Route('/StrasVTC/ConfirmationDeCourse/{id}', name: 'app_confirmationCourse')]
    public function confirmationCourse(Course $course): Response {
   
   
        return $this->render('course/validation.html.twig', [
        'controller_name' => 'CourseController',
        'course' => $course,
    ]);
    }

}   
