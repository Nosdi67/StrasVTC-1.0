<?php

namespace App\Repository;

use App\Entity\Avis;
use App\Entity\Course;
use App\Entity\Chauffeur;
use App\Entity\Utilisateur;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }
    public function findExistingAvis($userId,$chauffeurId,$courseId)
    {
        return $this->createQueryBuilder('a')
            ->select('a.id, a.noteChauffeur, a.noteCourse, a.text')
            ->andWhere('a.utilisateur = :user')
            ->andWhere('a.chauffeur = :chauffeur')
            ->andWhere('a.course = :course')
            ->setParameter('user', $userId)
            ->setParameter('chauffeur', $chauffeurId)
            ->setParameter('course', $courseId)
            ->getQuery()
            ->getOneOrNullResult();
    }
    

    //    /**
    //     * @return Avis[] Returns an array of Avis objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Avis
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
