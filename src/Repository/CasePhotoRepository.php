<?php
// src/Repository/CasePhotoRepository.php

namespace App\Repository;

use App\Entity\CasePhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CasePhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CasePhoto::class);
    }
}
