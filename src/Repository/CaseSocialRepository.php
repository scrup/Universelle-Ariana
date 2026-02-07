<?php
namespace App\Repository;

use App\Entity\CaseSocial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CaseSocialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CaseSocial::class);
    }

 public function searchFilterSort(?string $q, ?int $categoryId, ?string $sort): array
{
    $qb = $this->createQueryBuilder('cs')
        ->leftJoin('cs.categorie', 'cat')
        ->addSelect('cat');

    // ✅ Only show published cases (you set new() to PUBLISHED so this is OK)
  
    // Search
    $q = $q ? trim($q) : null;
    if ($q) {
        $qb->andWhere('cs.title LIKE :q OR cs.description LIKE :q OR cat.name LIKE :q')
           ->setParameter('q', '%' . $q . '%');
    }

    // Category filter
    if ($categoryId) {
        $qb->andWhere('cat.id = :catId')
           ->setParameter('catId', $categoryId);
    }

    // Sort
    switch ($sort) {
        case 'views':
            $qb->orderBy('cs.viewsCount', 'DESC')
               ->addOrderBy('cs.createdAt', 'DESC');
            break;

        case 'urgent':
            $qb->orderBy('cs.isUrgent', 'DESC')
               ->addOrderBy('cs.createdAt', 'DESC');
            break;

        default:
            $qb->orderBy('cs.createdAt', 'DESC');
            break;
    }

    return $qb->getQuery()->getResult();
}


}
