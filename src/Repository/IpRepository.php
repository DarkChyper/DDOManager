<?php

namespace App\Repository;

use App\Entity\Ip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ip>
 *
 * @method Ip|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ip|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ip[]    findAll()
 * @method Ip[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ip::class);
    }

    public function add(Ip $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ip $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getLastIp()
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.updateAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_OBJECT);
    }

//    /**
//     * @return Ip[] Returns an array of Ip objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Ip
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
