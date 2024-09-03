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
            ->setParameter('chauffeur', $chauffeur)// Définit le paramètre chauffeur
            ->setParameter('dateDepart', $dateDepart)
            ->setParameter('actualAvailableTime', $actualAvailableTime);
    
        $existingEvents = $qb->getQuery()->getResult();
    
        return count($existingEvents) === 0; // Retourne true si aucun événement n'est trouvé, donc chauffeur disponible
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
