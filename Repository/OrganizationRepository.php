<?php

namespace Pintushi\Bundle\OrganizationBundle\Repository;

use Pintushi\Bundle\OrganizationBundle\Entity\Organization;
use Videni\Bundle\RestBundle\Doctrine\ORM\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function getGlobalOrganization()
    {
        $qb = $this
            ->createQueryBuilder('o')
            ->andWhere('o.global = true')
        ;

        return $qb
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

      /**
     * Returns user organizations by name
     *
     * @param User   $user
     * @param string $name
     * @param bool   $useLikeExpr  Using expr()->like by default and expr()->eq otherwise
     * @param bool   $singleResult If we expected only one result
     *
     * @return Organization[]
     */
    public function getEnabledByUserAndName(User $user, $name, $useLikeExpr = true, $singleResult = false)
    {
        $qb = $this->createQueryBuilder('org');
        $qb->select('org')
            ->join('org.users', 'user')
            ->where('org.enabled = true')
            ->andWhere('user.id = :user')
            ->setParameter('user', $user);

        if ($useLikeExpr) {
            $qb->andWhere($qb->expr()->like('org.name', ':orgName'))
                ->setParameter('orgName', '%' . str_replace(' ', '%', $name) . '%');
        } else {
            $qb->andWhere($qb->expr()->eq('org.name', ':orgName'))
                ->setParameter('orgName', $name);
        }

        $query = $qb->getQuery();

        return $singleResult ? $query->getOneOrNullResult() : $query->getResult();
    }

     /**
     * Get user organization by id
     *
     * @param User    $user
     * @param integer $id
     * @return Organization
     */
    public function getEnabledUserOrganizationById(User $user, $id)
    {
        return $user->getOrganizations()->filter(
            function (Organization $item) use ($id) {
                return $item->getId() == $id && $item->isEnabled();
            }
        );
    }

    /**
     * @param array|null $orgIds
     *
     * @return Organization[]
     */
    public function getEnabledOrganizations(array $orgIds = [])
    {
        $queryBuilder = $this->createQueryBuilder('org');

        $queryBuilder->select('org');
        if ($orgIds) {
            $queryBuilder
                ->where('org.id in (:ids)')
                ->andWhere('org.enabled = true')
                ->setParameter('ids', $orgIds)
            ;
        } else {
            $queryBuilder->where('org.enabled = true');
        }

        return $queryBuilder->getQuery()->execute();
    }

     /**
     * Returns partial organizations data
     *
     * @param array $fields    array with fields should be returned
     * @param array $sortOrder order condition
     * @param array $ids array with organizations ids data should be limited
     *
     * @return array
     */
    public function getOrganizationsPartialData(array $fields, array $sortOrder = [], array $ids = [])
    {
        array_walk($fields, [QueryBuilderUtil::class, 'checkIdentifier']);
        $organizationsQueryQB = $this->createQueryBuilder('org')
            ->select(sprintf('partial org.{%s}', implode(', ', $fields)));
        if (count($sortOrder) !== 0) {
            foreach ($sortOrder as $fieldName => $direction) {
                $organizationsQueryQB->addOrderBy(
                    QueryBuilderUtil::getField('org', $fieldName),
                    QueryBuilderUtil::getSortOrder($direction)
                );
            }
        }

        if (count($ids) !== 0) {
            $organizationsQueryQB->where('org.id in (:ids)')
                ->setParameter('ids', $ids);
        }

        return $organizationsQueryQB->getQuery()->getArrayResult();
    }
}
