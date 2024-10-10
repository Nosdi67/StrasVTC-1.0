<?php

namespace App\Repository;

use App\Entity\Chauffeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chauffeur>
 */
class ChauffeurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chauffeur::class);
    }
    public function isChauffeurAvailable(Chauffeur $chauffeur, \DateTimeInterface $dateDepart, \DateTimeInterface $actualAvailableTime): bool
    {
        $qb = $this->createQueryBuilder('c')// Crée un constructeur de requête
            ->select('e')// Sélectionne les événements
            ->from('App\Entity\Evenement', 'e')// Spécifie la table des événements
            ->where('e.chauffeur = :chauffeur')// Filtre les événements en fonction du chauffeur
            ->andWhere('e.debut < :actualAvailableTime')// Filtre les événements qui se terminent après l'heure actuelle
            ->andWhere('e.fin > :dateDepart')// Filtre les événements qui commencent avant la date de départ
            ->setParameter('chauffeur', $chauffeur)// Définit le paramètre chauffeur pour eviter une injection SQL
            ->setParameter('dateDepart', $dateDepart)
            ->setParameter('actualAvailableTime', $actualAvailableTime);
    
        $existingEvents = $qb->getQuery()->getResult();
    
        return count($existingEvents) === 0; // Retourne true si aucun événement n'est trouvé, donc chauffeur disponible
    }
    public function findAvailableChauffeursByVehiculeType(string $vehiculeType, \DateTimeInterface $start, \DateTimeInterface $end)
    {
    $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT c
            FROM App\Entity\Chauffeur c
            INNER JOIN App\Entity\Vehicule v WITH v.chauffeur = c
            WHERE v.categorie = :vehiculeType
            AND c.id NOT IN (
                SELECT IDENTITY(e.chauffeur)
                FROM App\Entity\Evenement e
                WHERE (e.debut <= :endDate AND e.fin >= :startDate)
            )'
        )
        ->setParameter('vehiculeType', $vehiculeType)
        ->setParameter('startDate', $start)
        ->setParameter('endDate', $end);
        
       return $query->getResult();
// Création d'une sous-requête pour identifier les chauffeurs ayant des événements qui chevauchent la période
// $subQuery = $this->getEntityManager()->createQueryBuilder()
//     ->select('IDENTITY(ev.chauffeur)') // Récupérer l'ID du chauffeur
//     ->from('App\Entity\Evenement', 'ev')
//     ->where('ev.debut < :end') // Chevauchement si l'événement commence avant la fin de la période
//     ->andWhere('ev.fin > :start') // Chevauchement si l'événement se termine après le début de la période
//     ->setParameter('start', $start)
//     ->setParameter('end', $end);
// ajout de la sous-requête au constructeur de requête principal

// Cette ligne ajoute une condition à la requête principale pour exclure les chauffeurs
// qui sont déjà occupés pendant la période spécifiée.
// - $qb->expr()->notIn() crée une expression "NOT IN" en SQL
// - 'c.id' représente l'ID du chauffeur dans la requête principale
// - $subQuery->getDQL() obtient la sous-requête sous forme de chaîne DQL
// par cette approche je recupere les chauffeurs qui sont dispo pour cette periode
// $qb->andWhere($qb->expr()->notIn('c.id', $subQuery->getDQL()));

$query = $qb->getQuery();
return $query->getResult();

}
    public function findChauffeursByVehiculeType(string $vehiculeType)
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            // recuperation de l'entité Chauffeur et de l'entité Vehicule
            'SELECT c 
            FROM App\Entity\Chauffeur c
            INNER JOIN App\Entity\Vehicule v
            WITH v.chauffeur = c
            WHERE v.categorie = :vehiculeType'
        )->setParameter('vehiculeType', $vehiculeType);
            // WITH est utilisé pour ajouter des conditions supplémentaires à la jointure. 
            //Dans ce cas, v.chauffeur = c est la condition de jointure qui indique que le champ 
            //chauffeur de l'entité Vehicule doit correspondre à l'entité Chauffeur actuelle.
            // Vehicule type corresspond à la catégorie du véhicule selectionée dans la page de reservation
        return $query->getResult();
    }
    //    /**
    //     * @return Chauffeur[] Returns an array of Chauffeur objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Chauffeur
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
