<?php

namespace Pintushi\Bundle\OrganizationBundle\Ownership;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Pintushi\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Pintushi\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Pintushi\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Pintushi\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ServiceLink;
use Pintushi\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Pintushi\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Pintushi\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Pintushi\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Pintushi\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Pintushi\Bundle\SecurityBundle\Helper\GrantedInfoHelper;
use Pintushi\Bundle\OrganizationBundle\Entity\Organization;

/**
 * 设置实体默认ownership
 *
 */
class RecordOwnerData
{
    /** @var TokenAccessor*/
    protected $tokenAccessor;

    /** @var ServiceLink */
    protected $ownershipConfigProviderLink;

    protected $businessUnitManagerLink;

    protected $ownershipMetadataProviderLink;

    protected $treeProviderLink;

    protected $grantedInfoAccessorLink;

    protected $isAssignGranted;

    protected $accessLevel;

    public function __construct(
        TokenAccessor $tokenAccessor,
        ServiceLink $ownershipConfigProviderLink,
        ServiceLink $businessUnitManagerLink,
        ServiceLink $ownershipMetadataProviderLink,
        ServiceLink $treeProviderLink,
        ServiceLink $grantedInfoAccessorLink
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->ownershipConfigProviderLink= $ownershipConfigProviderLink;
        $this->businessUnitManagerLink = $businessUnitManagerLink;
        $this->ownershipMetadataProviderLink = $ownershipMetadataProviderLink;
        $this->treeProviderLink = $treeProviderLink;
        $this->grantedInfoAccessorLink = $grantedInfoAccessorLink;
    }

    /**
     * @throws \LogicException when getOwner method isn't implemented for entity with ownership type
     */
    public function process($entity)
    {
        if (!$this->tokenAccessor->hasUser()) {
            return;
        }

        $className = ClassUtils::getClass($entity);
        if ($this->ownershipConfigProviderLink->getService()->hasConfig($className)) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $config = $this->ownershipConfigProviderLink->getService()->getConfig($className);
            $ownerType = $config->get('owner_type');
            $ownerFieldName = $config->get('owner_field_name');
            // set default owner for organization and user owning entities
            if ($ownerType &&
                !$accessor->getValue($entity, $ownerFieldName)
            ) {
                $this->setOwner($ownerType, $entity, $ownerFieldName);
            }
            //set organization
            $this->setDefaultOrganization($config, $entity);
        }
    }

    /**
     * @param ConfigInterface $config
     * @param object          $entity
     */
    protected function setDefaultOrganization(ConfigInterface $config, $entity)
    {
        if ($config->has('organization_field_name')) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $fieldName = $config->get('organization_field_name');
            if (!$accessor->getValue($entity, $fieldName)) {
                $accessor->setValue(
                    $entity,
                    $fieldName,
                    $this->tokenAccessor->getOrganization()
                );
            }
        }
    }

    /**
     * @param string $ownerType
     * @param object $entity
     * @param string $ownerFieldName
     */
    protected function setOwner($ownerType, $entity, $ownerFieldName)
    {
        $user = $this->tokenAccessor->getUser();
        $accessor = PropertyAccess::createPropertyAccessor();
        $owner = null;
        if (OwnershipType::OWNER_TYPE_USER == $ownerType) {
            $owner = null;
            if ($user instanceof User) {
                $owner = $user;
            } elseif ($user->getOwner() instanceof User) {
                $owner = $user->getOwner();
            }
        }

        if (OwnershipType::OWNER_TYPE_ORGANIZATION == $ownerType) {
            $owner =  $this->tokenAccessor->getOrganization();
        }

        if (OwnershipType::OWNER_TYPE_BUSINESS_UNIT == $ownerType) {
            $permission =  $entity->getId()? 'ASSIGN': 'CREATE';
            $entityClass = get_class($entity);
            if (!$this->checkIsBusinessUnitEntity($entityClass)) {
                list($this->isAssignGranted, $this->accessLevel) =  $this->grantedInfoAccessorLink->getService()->getGrantedInfo($permission, 'entity:'.$entityClass);
                $owner = $this->getCurrentBusinessUnit($this->tokenAccessor->getOrganization());
            }
        }

        if ($owner) {
            $accessor->setValue(
                $entity,
                $ownerFieldName,
                $owner
            );
        }
    }

    /**
     * @param Organization $organization
     *
     * @return null|BusinessUnit
     */
    protected function getCurrentBusinessUnit(Organization $organization)
    {
        $user = $this->tokenAccessor->getUser();

        if (!$this->isAssignGranted) {
            return $user->getBusinessUnits()
                ->filter(function (BusinessUnit $businessUnit) use ($organization) {
                    return $businessUnit->getOrganization()->getId() === $organization->getId();
                })
                ->first();
        }

        if ($businessUnit = $this->businessUnitManagerLink->getService()->getCurrentBusinessUnit($user, $organization)) {
            return $businessUnit;
        }

        $owner = $user->getOwner();
        if ($owner instanceof BusinessUnit && $this->isBusinessUnitAvailableForCurrentUser($owner)) {
            return $user->getOwner();
        }

        return null;
    }

    /**
     * Get business units ids for current user for current access level
     *
     * @return array
     *  value -> business unit id
     */
    protected function getBusinessUnitIds()
    {
        if (AccessLevel::SYSTEM_LEVEL == $this->accessLevel) {
            return $this->businessUnitManagerLink->getService()->getBusinessUnitIds();
        } elseif (AccessLevel::LOCAL_LEVEL == $this->accessLevel) {
            return $this->treeProviderLink->getService()->getTree()->getUserBusinessUnitIds(
                $this->currentUser->getId(),
                $this->getOrganizationId()
            );
        } elseif (AccessLevel::DEEP_LEVEL === $this->accessLevel) {
            return $this->treeProviderLink->getService()->getTree()->getUserSubordinateBusinessUnitIds(
                $this->currentUser->getId(),
                $this->getOrganizationId()
            );
        } elseif (AccessLevel::GLOBAL_LEVEL === $this->accessLevel) {
            return $this->businessUnitManagerLink->getService()->getBusinessUnitIds($this->getOrganizationId());
        }

        return [];
    }

    /**
     * @param BusinessUnit $businessUnit
     * @return bool
     */
    protected function isBusinessUnitAvailableForCurrentUser(BusinessUnit $businessUnit)
    {
        return in_array($businessUnit->getId(), $this->getBusinessUnitIds());
    }

     /**
     * Check if current entity is BusinessUnit
     *
     * @param string $className
     *
     * @return bool
     */
    protected function checkIsBusinessUnitEntity($className)
    {
        $businessUnitClass = $this->ownershipMetadataProviderLink->getService()->getBusinessUnitClass();
        if ($className != $businessUnitClass && !is_subclass_of($className, $businessUnitClass)) {
            return false;
        }

        return true;
    }

     /**
     * @return int|null
     */
    protected function getOrganizationId()
    {
        return $this->tokenAccessor->getOrganization()->getId();
    }
}
