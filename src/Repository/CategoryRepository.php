<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findOneBySlug(string $slug): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllWithSubCategories()
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.subCategories', 'sc')
            ->addSelect('sc')
            ->getQuery()
            ->getResult();
    }

    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function createQueryBuilderForCategoriesWithoutSubCategories()
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.subCategories', 's')
            ->where('s.id IS NULL')  // Filtra solo las categorías sin subcategorías
            ->orderBy('c.name', 'ASC');
    }

    public function createQueryBuilderForCategoriesWithoutParents()
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.parents', 'p')
            ->where('p.id IS NULL')  // Solo categorías sin padres
            ->orderBy('c.name', 'ASC');
    }
    
    //    /**
    //     * @return Category[] Returns an array of Category objects
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

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
