<?php

namespace App\Controller;


use App\Entity\Course;
use DateTimeImmutable;
use App\Form\CourseType;
use App\Entity\Chauffeur;
use App\Entity\Evenement;
use App\Entity\Utilisateur;
use App\Service\PdfService;
use App\Service\MailerService;
use App\Repository\AvisRepository;
use App\Repository\ChauffeurRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CourseController extends AbstractController
{
    private $mailerService;
    // Injection du service MailerService dans le constructeur
    public function __construct(MailerService $mailerService)
    {
        $this->mailerService = $mailerService;
    }
    #[Route('/StrasVTC/course/', name: 'app_new_course', methods: ['POST', 'GET'])]
    public function index(Course $course = null, Evenement $event = null, Request $request, ChauffeurRepository $chauffeurRepository, EntityManagerInterface $entityManagerInterface,AvisRepository $avisRepository): Response
    {      
    $session = $request->getSession();
    $routeData = $session->get('route_data');
    $chauffeurId = $session->get('chauffeurId');
    $utilisateur = $this->getUser();
    $courseForm = $this->createForm(CourseType::class, $course);
    $courseForm->handleRequest($request);   

    if ($routeData && $chauffeurId) { 
        $addressDepart = $courseForm->get('adresseDepart')->getData();
        $addressArrivee = $courseForm->get('adresseArivee')->getData();
        $dateString = $routeData['dateDepart'];
        $dateDepart = new DateTime($dateString);
        $nbPassager = $routeData['nbPassager'];
        $vehicule = $routeData['vehiculeType'];
        $chauffeur = $chauffeurRepository->find($chauffeurId);
        $telephoneClient = $request->request->get('telephone');    

        // Récupérer les coordonnées de départ et d'arrivée de la session
        $startLat = $routeData['startLat'];
        $startLng = $routeData['startLng'];
        $endLat = $routeData['endLat'];
        $endLng = $routeData['endLng'];
        $roundTarif = null;
        // floatval = convertir une chaine de caractere en nombre decimal float
        $clientDistance = floatval($routeData['clientDistance']) / 1000; 
        
        // $durationDifference = abs($calculatedData['duration'] - $routeData['clientDuration']);
        // Comparer les valeurs calculées avec celles stockées en session
        // Appeler la fonction calculateRoute pour recalculer la distance, durée, et tarif côté serveur
        $calculatedData = $this->calculateRoute($startLat, $startLng, $endLat, $endLng);
        $duration = $calculatedData['duration'];
        $distanceDifference = abs($calculatedData['distance'] - $clientDistance); // abs = absolute value
        $tarifDifference = abs(floatval($calculatedData['tarifTest']) - floatval($routeData['clientTarif']));
        // Si les différences sont acceptables, continuer
        if ($distanceDifference > 0.1 || $tarifDifference > 0.1 ) {
            throw new \Exception('Les données calculées côté serveur ne correspondent pas aux données stockées en session.');
        }else{

        $roundTarif = round($calculatedData['tarifTest'], 0);
        }

        if ($courseForm->isSubmitted() && $courseForm->isValid()) {
            // Calcul de la date de fin de la course
            $dateFin = (clone $dateDepart)->add(new \DateInterval('PT' . $duration . 'M')); // P = Period, T = Time, M = Minute
            // Calcul de la distance de retour
            $returnDistance = $calculatedData['distance'];
            // Calcul du temps de retour et ajout d'un buffer de sécurité
            $returnTime = ($returnDistance / 100) * 60; // Temps de retour en minutes
            $securityBuffer = $returnTime * 0.3; // Buffer de sécurité de 30% du temps de retour
            $totalTime = round($returnTime + $securityBuffer); // Convertir en entier après avoir arrondi
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
            $course->setTelephoneClient($telephoneClient);

            $event = new Evenement();
            $event->setChauffeur($chauffeur);
            $event->setTitre($addressDepart . ' - ' . $addressArrivee);
            $event->setDebut($dateDepart);
            $event->setFin($dateFin);

            // Enregistrer la course et l'événement dans la base de données
            $entityManagerInterface->persist($course);
            $entityManagerInterface->persist($event);
            $entityManagerInterface->flush();
            
            // Envoi de l'email de confirmation
            $this->mailerService->sendCourseConfirmationForChauffeur($chauffeur,$utilisateur, $course);
            $this->mailerService->sendCourseConfirmationForUser($utilisateur, $course);
            
            // Supprimer les données de route de la session après la création de la course
            $session->remove('route_data');
            $session->remove('chauffeurId');
            return $this->redirectToRoute('app_confirmationCourse', [
                'id' => $course->getId(),
            ]);
        }
        // Récupérer les avis du chauffeur
        $allAvis = $avisRepository->findBy(['chauffeur' => $chauffeur]);
        shuffle($allAvis);// shuffle pour melanger les données d'un tableau
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
        $chauffeurId = filter_var($request->request->get('chauffeurId'), FILTER_VALIDATE_INT);
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
        // dd($utilisateur);
        //FILTER_SANITIZE_FULL_SPECIAL_CHARS : supprime les caractères spéciaux pour une chaine de caractères
        // FILTER_SANITIZE_NUMBER_INT : supprime les caractères spéciaux pour un nombre entier
        // FILTER_SANITIZE_NUMBER_FLOAT : supprime les caractères spéciaux pour un nombre à virgule 
        // FILTER_FLAG_ALLOW_FRACTION : autorise les nombres à virgule
        // ENT_QUOTES : les guillemets simples et doubles de la chaîne sont convertis en entités HTML
        // UTF-8 : encodage des caractères
        $session = $request->getSession();
        $dateDepart = filter_var($request->request->get('date'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $vehiculeType = filter_var($request->request->get('vehicle'),FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nbPassager = filter_var($request->request->get('passengers'), FILTER_SANITIZE_NUMBER_INT);
        $clientDistance = filter_var($request->request->get('clientDistance'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $clientDuration = filter_var($request->request->get('clientDuration'), FILTER_SANITIZE_NUMBER_INT);
        $clientTarif = filter_var($request->request->get('clientTarif'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $startLat = filter_var($request->request->get('startLat'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $startLng = filter_var($request->request->get('startLng'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $endLat = filter_var($request->request->get('endLat'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $endLng = filter_var($request->request->get('endLng'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $startAddress = htmlspecialchars($request->request->get('startAddress'), ENT_QUOTES, 'UTF-8');
        $endAddress = htmlspecialchars($request->request->get('endAddress'), ENT_QUOTES, 'UTF-8');
        $clientDuration = round($clientDuration / 60, 0); // convertir en minutes, 0 decimales 
       
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
        return $this->redirectToRoute('app_choix_chauffeur');
    }
    #[Route('/StrasVTC/course/Choix-Chauffeur', name: 'app_choix_chauffeur')]
    public function choixChauffeur(ChauffeurRepository $chauffeurRepository, Request $request): Response
    {
    // Récupération de la session
    $session = $request->getSession();
    // dd($session);
    $routeData = $session->get('route_data'); // récupération des données route_data stockées en session

    // Récupération et conversion de la date de départ
    $dateString = $routeData['dateDepart']; // récupération de la date de départ
    $dateDepart = new DateTime($dateString); // conversion de la date de départ en objet DateTime 
    $vehiculeType = $routeData['vehiculeType']; // récupération du type de véhicule

    // Calcul de la durée estimée de la course en minutes
    $clientDistanceMeters = $routeData['clientDistance']; // récupération de la distance en mètres
    $clientDistanceKm = $clientDistanceMeters / 1000; // conversion de la distance en kilomètres
    $duration = ($clientDistanceKm / 100) * 60; // Estimation du temps en minutes à une vitesse moyenne de 100 km/h
    $roundedDuration = round($duration); // Arrondi du temps    
    // Calcul de la date de fin de la course
    // modfiyer la date de départ en ajoutant la durée estimée de la course
    $dateFin = (clone $dateDepart)->modify("+{$roundedDuration} minutes");
    
    // Calcul de la distance de retour
    $returnDistanceKm = $clientDistanceKm; // Même distance pour le retour
    // Calcul du temps de retour en minutes et ajout d'un buffer de sécurité
    $returnTime = ($returnDistanceKm / 100) * 60; // Temps de retour en minutes
    $securityBuffer = $returnTime * 0.3; // Buffer de sécurité de 30% du temps de retour
    //arrondissage du temps de retour
    $totalTime = round($returnTime + $securityBuffer); // Convertir en entier après avoir arrondi

    // Calcul de l'heure à laquelle le chauffeur sera de nouveau disponible
    //P-> period, T->Time M-> Minute
    $actualAvailableTime = (clone $dateFin)->add(new \DateInterval('PT' . $totalTime . 'M')); // Ajout du temps en minutes
    // dd($actualAvailableTime);
    // Requête pour trouver tous les chauffeurs disponibles pour la date de départ choisie
    $chauffeurs = $chauffeurRepository->findAvailableChauffeursByVehiculeType($vehiculeType, $dateDepart, $actualAvailableTime);
    // dd($chauffeurs);
    // Calcul des notes des chauffeurs
    $chauffeurNotes = []; // tableau pour stocker les notes des chauffeurs
    foreach ($chauffeurs as $chauffeur) {
        $chauffeurNotes[$chauffeur->getId()] = $this->calculateAverageRating($chauffeur); //calcul de la note moyenne du chauffeur
    }
    // dd($chauffeurNotes);
    // Rendu de la vue avec les chauffeurs et leurs notes
    return $this->render('course/choixChauffeur.html.twig', [
        'chauffeurs' => $chauffeurs,
        'chauffeurNotes' => $chauffeurNotes,
    ]);
    }
    public function calculateAverageRating(Chauffeur $chauffeur): ?float
    {
        /// Récupération des avis du chauffeur
        $avis = $chauffeur->getAvis();
        // initaliser le rating total à 0
        $totalRating = 0;
        // compter le nombre d'avis
        $count = count($avis);
        // si pas d'avis, retourner null
        if ($count === 0) {
            return null;
        }
        // sinon, calculer le rating total
        foreach ($avis as $avis) {
            // ajouter la note de chaque avis au rating total
            $totalRating += $avis->getNoteChauffeur();
        }
        // retourner la division du rating total par le nombre d'avis pour obtenir la note moyenne
        return $totalRating / $count;
    }

        public function calculateRoute($startLat, $startLng, $endLat, $endLng){
        $url = "http://router.project-osrm.org/route/v1/driving/$startLng,$startLat;$endLng,$endLat?overview=false&steps=true";
        // creation d'une session client url (curl)
        //une session cURL est le processus par lequel un outil cURL est utilisé pour 
        //initier et maintenir une connexion HTTP afin d'interagir avec un serveur via des requêtes et des réponses
        $ch = curl_init(); //initalisation d'une session curl   
        curl_setopt($ch, CURLOPT_URL, $url);//definition de l'url d'api
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//retourne le resultat de la requete sous forme d'une chaine de caractere
        $response = curl_exec($ch);//execution de la requete curl
        curl_close($ch);//fermeture de la session curl

        $apiData = json_decode($response, true);//decodage de la reponse json
        $features = null;
        if (!isset($apiData['code']) || $apiData['code'] !=='Ok' ||!isset($apiData['waypoints']) || count($apiData['waypoints']) !== 2)    {
            throw new \Exception('No features found in the API response');
        }
        $features = $apiData['routes']; // Initialisation de $features
        $coordinates = $apiData['waypoints'];
        
    
        if(isset($coordinates) && count($coordinates) >= 2) { //si les coordonees sont presentes
            $startLat = $coordinates[0]['location'][1];//on les recupere ici
            $startLng = $coordinates[0]['location'][0];
            $endLat = $coordinates[1]['location'][1];
            $endLng = $coordinates[1]['location'][0];
        }else{
            throw new \Exception('No coordinates found');
        }
        if (isset($features[0]['distance']) && isset($features[0]['duration'])) {
            // Récupération de la distance et de la durée
            $distance = $features[0]['distance'] / 1000;// Distance en kilomètres
            $duration = $features[0]['duration'];// Durée en minutes
            $duration = round($features[0]['duration'] / 60,0);// Durée en minutes , arrondi a 0 decimal

        } else {
            throw new \Exception('distance and duration data unavailable');
        }

        $tarifTest=$distance*1.5 ;

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
    #[Route('/StrasVTC/Course/{id}/facture', name: 'app_course_facture')]
    public function facture(Course $course,PdfService $pdfService): Response
    {
        $user = $this->getUser();
        $societe = $course->getChauffeur()->getSociete();
        $chauffeur = $course->getChauffeur();

        if ($course->getUtilisateur() !== $user && $course->getChauffeur() !== $chauffeur) {
            // Si l'utilisateur n'est ni le client ni le chauffeur, une réponse 403, acces denile 
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette facture.');
        }

        $html=$this->renderView('facture/facture.html.twig', [
            'course' => $course,
            'societe' => $societe,
            'user' => $user,
        ]);
        $pdfContnet= $pdfService->generatePDF($html);//appeler le service
        return new Response($pdfContnet, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="facture.pdf"',
        ]);
    }

    #[Route('/StrasVTC/ConfirmationDeCourse/{id}', name: 'app_confirmationCourse')]
    public function confirmationCourse(Course $course,AvisRepository $avisRepository): Response {
        $chauffeur = $course->getChauffeur();
        
        // Récupérer les avis du chauffeur
        $allAvis = $avisRepository->findBy(['chauffeur' => $chauffeur]);
        shuffle($allAvis);
        $randomAvis = array_slice($allAvis, 0, 5);//afficher 5 avis random
        //appel la classe pour formater la date
        // fr pour la langue et les dates
        //le parametre long est pour la date et short pour l'heure
        $formatDate = new IntlDateFormatter ('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::SHORT, 'Europe/Paris');
        $formattedDate = $formatDate->format($course->getDateDepart());

        return $this->render('course/validation.html.twig', [
        'course' => $course,
        'chauffeur' => $chauffeur,
        'randomAvis' => $randomAvis,
        'formattedDate' => $formattedDate,
        ]);
    }
    
    // fonction qui permet de verifier si un point est dans un polygone
    // c'est un algoritme Ray Casting.
    public function isPointInPolygon($lat, $lng, $polygon) {
        $inside = false; // initialisation d'un boolean
        $x = $lng; // longitude
        $y = $lat; // latitude
    
        $pointNumbers= count($polygon); // nombre de points du polygone
        // boucle pour parcourir les points du polygone
        // 1ere expression : initalisation de d'index.
        // 2eme expression : $j = au total  des points du polygone -1.
        // 3eme expression : tant que $i est < au total des points du polygone, la boucle continue
        // 4eme expression : avant d'incrementer l'index ($i), on assigne la valeur de $i à $j
        // cela signifie que $j garde la valeur du sommet actuel avant que $i ne passe au sommet suivant.
        for ($i = 0, $j = $pointNumbers - 1; $i < $pointNumbers; $j = $i++) {
            // on recupere les coordonnees des points du polygone
            $xi = $polygon[$i][1];
            $yi = $polygon[$i][0];
            $xj = $polygon[$j][1];
            $yj = $polygon[$j][0];
            // on verifie si le point est dans le polygone
            // le principe est le suivant : on recupere un poin de coordonées fournis de notre point de départ
            // ensuite, l'algorithme trace une droite entre ce point et un autre point du polygone
            // si le point est a gauche de la droite, le point est dans le polygone
            // si le point est a droite de la droite, le point est en dehors du polygone
            // si le point est sur la droite, le point est sur le polygone
            $intersect = (($yi > $y) != ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);
            if ($intersect) {
                $inside = !$inside;
            }
        }
    
        return $inside;
    }

}   
