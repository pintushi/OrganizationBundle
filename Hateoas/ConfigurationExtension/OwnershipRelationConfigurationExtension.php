<?php

namespace Pintushi\Bundle\OrganizationBundle\Hateoas\ConfigurationExtension;

use Hateoas\Configuration\Metadata\ClassMetadataInterface;
use Hateoas\Configuration\Relation;
use Hateoas\Configuration\Embedded;
use Hateoas\Configuration\Exclusion;
use Hateoas\Configuration\Metadata\ConfigurationExtensionInterface;
use Pintushi\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Pintushi\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Hateoas\Configuration\Route;
use Pintushi\Bundle\OrganizationBundle\Entity\BusinessUnit;

class OwnershipRelationConfigurationExtension implements ConfigurationExtensionInterface
{
    private $excluded = [ BusinessUnit::class];

    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    protected $enabled = true;

    /**
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     */
    public function __construct(OwnershipMetadataProviderInterface $ownershipMetadataProvider)
    {
        $this->metadataProvider = $ownershipMetadataProvider;
    }

     /**
     * {@inheritDoc}
     */
    public function decorate(ClassMetadataInterface $classMetadata)
    {
        if (!$this->enabled) {
            return;
        }

        if (in_array($classMetadata->getName(), $this->excluded)) {
            return;
        }

        $ownershipMetadata = $this->metadataProvider->getMetadata($classMetadata->getName());
        if ($ownershipMetadata) {
            $this->addOwnershipRelation($classMetadata, $ownershipMetadata);
        }
    }

    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    private function addOwnershipRelation(ClassMetadataInterface $classMetadata, OwnershipMetadataInterface $ownershipMetadata)
    {
        if ($ownershipMetadata->isUserOwned()) {
            $classMetadata->addRelation(
                $this->createRelation($ownershipMetadata->getOwnerFieldName(), 'api_users_view', new Exclusion(['User']) )
            );
            $classMetadata->addRelation(
                $this->createOrganizationRelation($ownershipMetadata->getOrganizationFieldName())
            );

            return;
        }
        if ($ownershipMetadata->isBusinessUnitOwned()) {
            $classMetadata->addRelation(
                $this->createRelation($ownershipMetadata->getOwnerFieldName(), 'api_business_units_view', new Exclusion(['BusinessUnit']))
            );
            $classMetadata->addRelation(
                $this->createOrganizationRelation($ownershipMetadata->getOrganizationFieldName())
            );

            return;
        }

        if ($ownershipMetadata->isOrganizationOwned()) {
            $classMetadata->addRelation(
                $this->createOrganizationRelation($ownershipMetadata->getOwnerFieldName())
            );
        }
    }

    public function createOrganizationRelation($relationName)
    {
        $getterName = $this->getFieldMethodName($relationName);

        return $this->createRelation(
            $relationName,
            'api_organizations_view',
            new Exclusion(
                ['Organization'],
                null,
                null,
                sprintf("expr(!service('pintushi_security.token_accessor').%s().isGlobal())", $getterName)
            )
        );
    }

    private function createRelation($relationName, $route, $exclusion = null)
    {
        $getterName = $this->getFieldMethodName($relationName);

        return new Relation(
            $relationName,
            new Route($route, ['id' => sprintf('expr(object.%s().getId())', $getterName)]),
            new Embedded(sprintf('expr(object.%s())', $getterName)),
            [],
            $exclusion
        );
    }

    private function getFieldMethodName($fieldName)
    {
       return 'get'. ucfirst($fieldName);
    }
}
