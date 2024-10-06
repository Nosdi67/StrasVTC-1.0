<?php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }
    public function findAllCoursesById($coursePublicID): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.publicId = :publicId')
            ->setParameter('publicId', $coursePublicID)
            ->getQuery()
            ->getResult();
    }
    public function findAllCoursesByDate($date): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.date_depart = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
    public function findAllCoursesByUtilisateur($utilisateur): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.utilisateur_id = :client')
            ->setParameter('utilisateur', $utilisateur)
            ->getQuery()
            ->getResult();
    }
    public function findAllCoursesByChauffeur($chauffeur): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.chauffeur_id = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->getQuery()
            ->getResult();
    }
    public function findCoursesTerminees($user)
    {
        $currentDate = new \DateTime();

        return $this->createQueryBuilder('c')
            ->where('c.utilisateur = :user')
            ->andWhere('c.dateFin < :currentDate') 
            ->setParameter('user', $user)
            ->setParameter('currentDate', $currentDate)
            ->orderBy('c.dateFin', 'DESC')  
            ->getQuery()
            ->getResult();
    }
    public function findCoursesAVenir($user)
    {
        $currentDate = new \DateTime();

        return $this->createQueryBuilder('c')
            ->where('c.utilisateur = :user')
            ->andWhere('c.dateDepart > :currentDate')  
            ->setParameter('user', $user)
            ->setParameter('currentDate', $currentDate)
            ->orderBy('c.dateDepart', 'ASC')  
            ->getQuery()
            ->getResult();
    }
    
    //    /**
    //     * @return Course[] Returns an array of Course objects
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

    //    public function findOneBySomeField($value): ?Course
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
