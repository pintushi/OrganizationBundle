<?php

namespace Pintushi\Bundle\OrganizationBundle\Autocomplete;

use Doctrine\Common\Persistence\ManagerRegistry;
use Pintushi\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Pintushi\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Pintushi\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class OrganizationSearchHandler implements SearchHandlerInterface
{
    /** @var string */
    protected $className;

    /** @var array */
    protected $fields;

    /** @var array */
    protected $displayFields;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var PropertyAccessor */
    protected $accessor;

    /**
     * @param string                 $className
     * @param array                  $fields
     * @param array                  $displayFields
     * @param ManagerRegistry        $managerRegistry
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        $className,
        $fields,
        $displayFields,
        ManagerRegistry $managerRegistry,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->className = $className;
        $this->fields = $fields;
        $this->displayFields = $displayFields;
        $this->managerRegistry = $managerRegistry;
        $this->tokenAccessor = $tokenAccessor;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        $user = $this->tokenAccessor->getUser();
        $global = $this->tokenAccessor->getOrganization()->isGlobal();
        /** @var OrganizationRepository $repository */
        $repository = $this->managerRegistry->getRepository($this->className);
        if (!$searchById) {
            $items = $global? $repository->getEnabledByName($query) : $repository->getEnabledByUserAndName($user, $query);
        } else {
            $items = $global? $repository->find($query) : $repository->getEnabledUserOrganizationById($user, $query);
        }

        $resultsData = [];
        foreach ($items as $organization) {
            $resultsData[] = $this->convertItem($organization);
        }

        return [
            'results' => $resultsData,
            'more' => false
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->displayFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName()
    {
        return $this->className;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $result = [];
        foreach ($this->fields as $field) {
            $result[$field] = $this->accessor->getValue($item, $field);
        }

        return $result;
    }
}
