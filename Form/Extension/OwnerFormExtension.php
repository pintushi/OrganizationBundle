<?php

namespace Pintushi\Bundle\OrganizationBundle\Form\Extension;

use Pintushi\Bundle\OrganizationBundle\Form\Type\BusinessUnitAutocompleteChoiceType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\Common\Util\ClassUtils;
use Pintushi\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Pintushi\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Pintushi\Bundle\OrganizationBundle\Entity\Organization;
use Pintushi\Bundle\OrganizationBundle\Form\EventListener\OwnerFormSubscriber;
use Pintushi\Bundle\SecurityBundle\Acl\AccessLevel;
use Pintushi\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Pintushi\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Pintushi\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Pintushi\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Pintushi\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Pintushi\Bundle\SecurityBundle\ORM\DoctrineHelper;
use Pintushi\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Pintushi\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Pintushi\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Pintushi\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Class OwnerFormExtension
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OwnerFormExtension extends AbstractTypeExtension
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var string */
    protected $fieldName;

    /** @var string */
    protected $fieldLabel = 'pintushi.user.owner.label';

    /** @var bool */
    protected $isAssignGranted;

    /** @var string */
    protected $accessLevel;

    /** @var User */
    protected $currentUser;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var OwnerTreeProvider */
    protected $treeProvider;

    /** @var EntityOwnerAccessor */
    protected $entityOwnerAccessor;

    /**
     * @param DoctrineHelper                     $doctrineHelper
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     * @param BusinessUnitManager                $businessUnitManager
     * @param AuthorizationCheckerInterface      $authorizationChecker
     * @param TokenAccessorInterface             $tokenAccessor
     * @param AclVoter                           $aclVoter
     * @param OwnerTreeProvider                  $treeProvider
     * @param EntityOwnerAccessor                $entityOwnerAccessor
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        BusinessUnitManager $businessUnitManager,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        AclVoter $aclVoter,
        OwnerTreeProvider $treeProvider,
        EntityOwnerAccessor $entityOwnerAccessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->businessUnitManager = $businessUnitManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->aclVoter = $aclVoter;
        $this->treeProvider = $treeProvider;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \LogicException when getOwner method isn't implemented for entity with ownership type
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['ownership_disabled']) {
            return;
        }

        $formConfig = $builder->getFormConfig();
        if (!$formConfig->getCompound()) {
            return;
        }

        $dataClassName = $formConfig->getDataClass();
        if (!$dataClassName) {
            return;
        }

        $user = $this->getCurrentUser();
        if (!$user) {
            return;
        }

        $metadata = $this->getMetadata($dataClassName);

        if (!$metadata || $metadata->isOrganizationOwned()) {
            return;
        }

        $this->fieldName = $metadata->getOwnerFieldName();

        $this->checkIsGranted('CREATE', 'entity:' . $dataClassName);
        $defaultOwner = null;

        if ($metadata->isUserOwned() && $this->isAssignGranted) {
            $this->addUserOwnerField($builder, $dataClassName);
            $defaultOwner = $user;
        } elseif ($metadata->isBusinessUnitOwned()) {
            $this->addBusinessUnitOwnerField($builder, $user, $dataClassName);
            if (!$this->checkIsBusinessUnitEntity($dataClassName)) {
                $defaultOwner = $this->getCurrentBusinessUnit(
                    $this->getOrganization()
                );
            }
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);

        /**
         * Adding subscriber to hide owner field for update pages if assign permission is not granted
         * and set default owner value
         */
        $builder->addEventSubscriber(
            new OwnerFormSubscriber(
                $this->doctrineHelper,
                $this->fieldName,
                $this->fieldLabel,
                $this->isAssignGranted,
                $defaultOwner
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'ownership_disabled' => false,
            ]
        );
    }


    /**
     * Process form after data is set and remove/disable owner field depending on permissions
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->getParent()) {
            return;
        }
        $entity = $event->getData();

        //check assign for existed entity
        if (is_object($entity)
            && $entity->getId()
        ) {
            $permission = 'ASSIGN';
            $this->checkIsGranted($permission, $entity);
            $owner         = $this->entityOwnerAccessor->getOwner($entity);
            $dataClassName = ClassUtils::getClass($entity);
            $metadata      = $this->getMetadata($dataClassName);

            if ($metadata) {
                if ($form->has($this->fieldName)) {
                    $form->remove($this->fieldName);
                }
                if ($this->isAssignGranted) {
                    if ($metadata->isUserOwned()) {
                        $this->addUserOwnerField($form, $dataClassName, $owner);
                    } elseif ($metadata->isBusinessUnitOwned()) {
                        $this->addBusinessUnitOwnerField($form, $this->getCurrentUser(), $dataClassName);
                    }
                }
            }
        }
    }

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param array                              $data
     */
    protected function addUserOwnerField($builder, $dataClass, $data = null)
    {
        /**
         * Showing user owner box for entities with owner type USER if assign permission is
         * granted.
         */
        if ($this->isAssignGranted) {
            if (null !== $data) {
                $options['data'] = $data;
            }

            $builder->add(
                $this->fieldName,
                TextType::class,
                $options
            );
        }
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
        $businessUnitClass = $this->ownershipMetadataProvider->getBusinessUnitClass();
        if ($className != $businessUnitClass && !is_subclass_of($className, $businessUnitClass)) {
            return false;
        }

        return true;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param User                 $user
     * @param string               $className
     */
    protected function addBusinessUnitOwnerField($builder, User $user, $className)
    {
        /**
         * Owner field is required for all entities except business unit
         */
        if (!$this->checkIsBusinessUnitEntity($className)) {
            $validation      = [
                'constraints' => [new NotBlank(['groups' => ['pintushi']])],
                'required'    => true,
            ];
            $emptyValueLabel = 'pintushi.business_unit.form.choose_business_user';
        } else {
            $validation       = [
                'required' => false
            ];
            $emptyValueLabel  = 'pintushi.business_unit.form.none_business_user';
            $this->fieldLabel = 'pintushi.organization.businessunit.parent.label';
        }

        if ($this->isAssignGranted) {
            /**
             * If assign permission is granted, and user able to see business units, showing all available.
             * If not able to see, render default in hidden field.
             */
            if ($this->authorizationChecker->isGranted('VIEW', 'entity:' . BusinessUnit::class)) {
                $builder->add(
                    $this->fieldName,
                    TextType::class
                );
            } else {
                // Add hidden input with default owner only during creation process,
                // current user not able to modify this
                if ($builder instanceof FormBuilder) {
                    $transformer  = new EntityToIdTransformer(
                        $this->doctrineHelper->getEntityManager(BusinessUnit::class),
                        BusinessUnit::class
                    );
                    $builder->add(
                        $this->fieldName,
                        HiddenType::class
                    );
                    $builder->get($this->fieldName)->addModelTransformer($transformer);
                }
            }
        } else {
            $businessUnits = $user->getBusinessUnits();
            if (count($businessUnits)) {
                $builder->add(
                    $this->fieldName,
                    EntityType::class,
                    array_merge(
                        [
                            'class'                => BusinessUnit::class,
                            'property'             => 'name',
                            'query_builder'        => function (BusinessUnitRepository $repository) use ($user) {
                                $qb = $repository->createQueryBuilder('bu');
                                $qb->andWhere($qb->expr()->isMemberOf(':user', 'bu.users'));
                                $qb->setParameter('user', $user);

                                return $qb;
                            },
                            'mapped'               => true,
                            'label'                => $this->fieldLabel,
                            'translatable_options' => false
                        ],
                        $validation
                    )
                );
            }
        }
    }

    /**
     * @param Organization $organization
     *
     * @return null|BusinessUnit
     */
    protected function getCurrentBusinessUnit(Organization $organization)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return null;
        }

        if (!$this->isAssignGranted) {
            return $user->getBusinessUnits()
                ->filter(function (BusinessUnit $businessUnit) use ($organization) {
                    return $businessUnit->getOrganization()->getId() === $organization->getId();
                })
                ->first();
        }

        if ($businessUnit = $this->businessUnitManager->getCurrentBusinessUnit($user, $organization)) {
            return $businessUnit;
        }

        $owner = $user->getOwner();
        if ($owner instanceof BusinessUnit && $this->isBusinessUnitAvailableForCurrentUser($owner)) {
            return $user->getOwner();
        }

        return null;
    }

    /**
     * @return null|User
     */
    protected function getCurrentUser()
    {
        if (null === $this->currentUser) {
            $user = $this->tokenAccessor->getUser();
            if ($user && is_object($user) && $user instanceof User) {
                $this->currentUser = $user;
            }
        }

        return $this->currentUser;
    }

    /**
     * @return bool|Organization
     */
    protected function getCurrentOrganization()
    {
        $businessUnit = $this->getCurrentBusinessUnit($this->getOrganization());
        if (!$businessUnit) {
            return true;
        }

        return $businessUnit->getOrganization();
    }

    /**
     * @return int|null
     */
    protected function getOrganizationContextId()
    {
        return $this->getOrganization()->getId();
    }

    /**
     * Check is granting user to object in given permission
     *
     * @param string        $permission
     * @param object|string $object
     */
    protected function checkIsGranted($permission, $object)
    {
        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        $this->isAssignGranted = $this->authorizationChecker->isGranted($permission, $object);
        $this->accessLevel = $observer->getAccessLevel();
    }

    /**
     * Get metadata for entity
     *
     * @param object|string $entity
     *
     * @return bool|OwnershipMetadataInterface
     * @throws \LogicException
     */
    protected function getMetadata($entity)
    {
        if (is_object($entity)) {
            $entity = ClassUtils::getClass($entity);
        }
        if (!$this->doctrineHelper->isManageableEntity($entity)) {
            return false;
        }

        $metadata = $this->ownershipMetadataProvider->getMetadata($entity);

        return $metadata->hasOwner()
            ? $metadata
            : false;
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
            return $this->businessUnitManager->getBusinessUnitIds();
        } elseif (AccessLevel::LOCAL_LEVEL == $this->accessLevel) {
            return $this->treeProvider->getTree()->getUserBusinessUnitIds(
                $this->currentUser->getId(),
                $this->getOrganizationContextId()
            );
        } elseif (AccessLevel::DEEP_LEVEL === $this->accessLevel) {
            return $this->treeProvider->getTree()->getUserSubordinateBusinessUnitIds(
                $this->currentUser->getId(),
                $this->getOrganizationContextId()
            );
        } elseif (AccessLevel::GLOBAL_LEVEL === $this->accessLevel) {
            return $this->businessUnitManager->getBusinessUnitIds($this->getOrganizationContextId());
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
     * Gets organization from the current security token
     *
     * @return bool|Organization
     */
    protected function getOrganization()
    {
        return $this->tokenAccessor->getOrganization();
    }

    public function getExtendedType()
    {
        return FormType::class;
    }
}
