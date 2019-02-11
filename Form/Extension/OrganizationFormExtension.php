<?php

namespace Pintushi\Bundle\OrganizationBundle\Form\Extension;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Doctrine\Common\Util\ClassUtils;
use Pintushi\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Pintushi\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Pintushi\Bundle\SecurityBundle\ORM\DoctrineHelper;
use Pintushi\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Pintushi\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Pintushi\Bundle\OrganizationBundle\Entity\Organization;
use Pintushi\Bundle\OrganizationBundle\Form\EventListener\OwnerFormSubscriber;
use Pintushi\Bundle\OrganizationBundle\Form\EventListener\OrganizationFormSubscriber;
use Videni\Bundle\RestBundle\Form\DataTransformer\EntityToIdTransformer;
use Pintushi\Bundle\OrganizationBundle\Form\Type\OrganizationSelectType;
use Symfony\Component\HttpFoundation\RequestStack;
use Pintushi\Bundle\OrganizationBundle\Repository\OrganizationRepository;

class OrganizationFormExtension extends AbstractTypeExtension
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    protected $ownershipMetadataProvider;

    protected $authorizationChecker;

    protected $doctrineHelper;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    private $requestStack;

    private $organizationRepository;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        AuthorizationCheckerInterface $authorizationChecker,
        DoctrineHelper $doctrineHelper,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        OrganizationRepository $organizationRepository
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->organizationRepository = $organizationRepository;
    }

    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $formConfig = $builder->getFormConfig();
        if (!$formConfig->getCompound()) {
            return;
        }

        $dataClassName = $formConfig->getDataClass();
        if (!$dataClassName) {
            return;
        }

        $user = $this->tokenAccessor->getUser();
        if (!$user) {
            return;
        }

        $metadata = $this->getMetadata($dataClassName);
        if (!$metadata  || !$metadata->hasOwner()) {
            return;
        }
        // listener must be executed before validation
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 128);

        //show a disabled organization select for global organization
        $isGlobalOrganization = $this->tokenAccessor->getOrganization()->isGlobal();
        if (!$isGlobalOrganization) {
            return;
        }
        $organizationField = $metadata->getOrganizationFieldName();

        if ($this->authorizationChecker->isGranted('VIEW', 'entity:'. Organization::class)) {
            $builder->add($organizationField, OrganizationSelectType::class, [
                'disabled' => true
            ]);
        } else {
            $builder->add($organizationField, EntityType::class, [
                'class'                => Organization::class,
                'property'             => 'name',
                'query_builder'        => function (OrgnaizationRepository $repository) use ($user) {
                    $qb = $repository->createQueryBuilder('o');
                    $qb->andWhere($qb->expr()->isMemberOf(':user', 'o.users'));
                    $qb->setParameter('user', $user);

                    return $qb;
                },
                'disabled'               => false,
            ]);
        }

        $builder->addEventSubscriber(new OrganizationFormSubscriber(
            $organizationField,
            $this->requestStack,
            $this->organizationRepository,
            $this->getPropertyAccessor()
        ));

        $isAssignGranted = $this->authorizationChecker->isGranted('ASSIGN', 'entity:'. $dataClassName);
        $builder->addEventSubscriber(
            new OwnerFormSubscriber(
                $this->doctrineHelper,
                $organizationField,
                'pintushi.organiation.organization.label',
                $isAssignGranted,
                $this->tokenAccessor->getOrganization()
            )
        );
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $data = $event->getForm()->getData();

        if (is_array($data) || $data instanceof \Traversable) {
            foreach ($data as $value) {
                if (is_object($value)) {
                    $this->updateOrganization($value);
                }
            }
        } elseif (is_object($data)) {
            $this->updateOrganization($data);
        }
    }

    /**
     * @param object $entity
     */
    protected function updateOrganization($entity)
    {
        $organizationField = $this->ownershipMetadataProvider->getMetadata(ClassUtils::getClass($entity))->getOrganizationFieldName();
        if (!$organizationField) {
            return;
        }

        $organization = $this->getPropertyAccessor()->getValue($entity, $organizationField);
        if ($organization) {
            return;
        }

        $organization = $this->tokenAccessor->getOrganization();
        if (null === $organization) {
            return;
        }

        $this->getPropertyAccessor()->setValue($entity, $organizationField, $organization);
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
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

    public function getExtendedType()
    {
        return FormType::class;
    }
}
