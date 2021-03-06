<?php

namespace Pintushi\Bundle\OrganizationBundle\Repository;

use Pintushi\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Pintushi\Bundle\OrganizationBundle\Entity\Organization;
use Videni\Bundle\RestBundle\Doctrine\ORM\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

class BusinessUnitRepository extends ServiceEntityRepository
{
    private $organizationEntityClass;

    public function __construct(
        ManagerRegistry $registry,
        $businessUnitEntityClass = BusinessUnit::class,
        $organizationEntityClass = Organization::class
    ) {
        parent::__construct($registry, $businessUnitEntityClass);

        $this->organizationEntityClass = $organizationEntityClass;
    }

    /**
     * Finds the first record
     *
     * @return BusinessUnit
     */
    public function getFirst($organization = null)
    {
        $qb = $this->createQueryBuilder('businessUnit')
            ->select('businessUnit')
            ->orderBy('businessUnit.id')
            ;

        if ($organization) {
            $qb
                ->andWhere('IDENTITY(businessUnit.organization)=:organization')
                ->setParameter('organization', $organization->getId())
            ;
        }

        return $qb
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    /**
     * Build business units tree for user page
     *
     * @param User     $user
     * @param int|null $organizationId
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getBusinessUnitsTree(User $user = null, $organizationId = null)
    {
        $businessUnits = $this->createQueryBuilder('businessUnit')->select(
            [
                'businessUnit.id',
                'businessUnit.name',
                'IDENTITY(businessUnit.owner) parent',
                'IDENTITY(businessUnit.organization) organization',
            ]
        );
        if ($user && $user->getId()) {
            $units = $user->getBusinessUnits()->map(
                function (BusinessUnit $businessUnit) {
                    return $businessUnit->getId();
                }
            );
            $units = $units->toArray();
            if ($units) {
                $businessUnits->addSelect('CASE WHEN businessUnit.id IN (:userUnits) THEN 1 ELSE 0 END as hasUser');
                $businessUnits->setParameter(':userUnits', $units);
            }
        }

        if ($organizationId) {
            $businessUnits->where('businessUnit.organization = :organizationId');
            $businessUnits->setParameter(':organizationId', $organizationId);
        }

        $businessUnits = $businessUnits->getQuery()->getArrayResult();
        $children      = [];
        foreach ($businessUnits as &$businessUnit) {
            $parent              = $businessUnit['parent'] ? : 0;
            $children[$parent][] = & $businessUnit;
        }
        unset($businessUnit);
        foreach ($businessUnits as &$businessUnit) {
            if (isset($children[$businessUnit['id']])) {
                $businessUnit['children'] = $children[$businessUnit['id']];
            }
        }
        unset($businessUnit);
        if (isset($children[0])) {
            $children = $children[0];
        }

        return $children;
    }

    /**
     * Returns business units tree by organization
     * Or returns business units tree for given organization.
     *
     * @param int|null $organizationId
     * @param array $sortOrder array with order parameters. key - organization entity field, value - sort direction
     *
     * @return array
     */
    public function getOrganizationBusinessUnitsTree($organizationId = null, array $sortOrder = [])
    {
        $tree          = [];
        $businessUnits = $this->getBusinessUnitsTree();

        $organizations = $this->_em->getRepository($this->organizationEntityClass)
            ->getOrganizationsPartialData(
                ['id', 'name', 'enabled'],
                $sortOrder,
                $organizationId ? [$organizationId] : []
            );
        foreach ($organizations as $organizationItem) {
            $tree[$organizationItem['id']] = array_merge($organizationItem, ['children' => []]);
        }

        foreach ($businessUnits as $businessUnit) {
            if ($businessUnit['organization'] == null) {
                continue;
            }
            $tree[$businessUnit['organization']]['children'][] = $businessUnit;
        }

        if ($organizationId && isset($tree[$organizationId])) {
            return $tree[$organizationId]['children'];
        }

        return $tree;
    }

    /**
     * Get business units ids
     *
     * @param int|null $organizationId
     * @return array
     */
    public function getBusinessUnitIds($organizationId = null)
    {
        $result        = [];
        /** @var QueryBuilder $businessUnitsQB */
        $businessUnitsQB = $this->createQueryBuilder('businessUnit');
        $businessUnitsQB->select('businessUnit.id');
        if ($organizationId != null) {
            $businessUnitsQB
                ->where('businessUnit.organization = :organizationId')
                ->setParameter(':organizationId', $organizationId);
        }
        $businessUnits = $businessUnitsQB
            ->getQuery()
            ->getArrayResult();

        foreach ($businessUnits as $buId) {
            $result[] = $buId['id'];
        }

        return $result;
    }

    /**
     * @param array $businessUnits
     * @return mixed
     */
    public function getBusinessUnits(array $businessUnits)
    {
        return $this->createQueryBuilder('businessUnit')
            ->select('businessUnit')
            ->where('businessUnit.id in (:ids)')
            ->setParameter('ids', $businessUnits)
            ->getQuery()
            ->execute();
    }

    /**
     * Get count of business units
     *
     * @return int
     */
    public function getBusinessUnitsCount()
    {
        $qb = $this->createQueryBuilder('businessUnit');
        $qb->select('COUNT(businessUnit.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->createQueryBuilder('businessUnit');
    }

    public function createListQueryBuilder()
    {
        return $this
            ->createQueryBuilder('o')
            ->leftJoin('o.owner')
        ;
    }
}
