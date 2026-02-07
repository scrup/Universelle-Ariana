<?php
namespace App\Repository;

use App\Entity\Donation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donation::class);
    }
    public function statsByCase(?int $caseId = null): array
{
    $qb = $this->createQueryBuilder('d')
        ->innerJoin('d.caseSocial', 'cs')
        ->innerJoin('cs.categorie', 'cat')
        ->addSelect('cs', 'cat')
       ->select('
        cs.id AS caseId,
        cs.title AS caseTitle,
        cat.name AS categoryName,
        cs.isUrgent AS isUrgent,
        cs.viewsCount AS viewsCount,
        COUNT(d.id) AS donationsCount,
        COALESCE(SUM(d.amount), 0) AS totalAmount,
        COALESCE(AVG(d.amount), 0) AS avgAmount
        ')

        ->groupBy('cs.id')
        ->orderBy('totalAmount', 'DESC');

    if ($caseId) {
        $qb->andWhere('cs.id = :caseId')->setParameter('caseId', $caseId);
    }

    return $qb->getQuery()->getArrayResult();
}

}
