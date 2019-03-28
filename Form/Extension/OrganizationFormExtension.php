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
use Videni\Bundle\RestBundle\Form\DataTransformer\EntityToIdTransformer;
use Pintushi\Bundle\OrganizationBundle\Form\Type\OrganizationSelectType;
use Symfony\Component\HttpFoundation\RequestStack;
use Pintushi\Bundle\OrganizationBundle\Repository\OrganizationRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationFormExtension extends AbstractTypeExtension
{
    public const QUERY_ID = '_org_id';

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
        OrganizationRepository $organizationRepository,
        RequestStack $requestStack
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->organizationRepository = $organizationRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
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

        $metadata = $this->getMetadata($dataClassName);
        if (!$metadata || !$metadata->hasOwner()) {
            return;
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);

        // listener must be executed before validation
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 128);
    }

    public function getExtendedType()
    {
        return FormType::class;
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $entity = $event->getData();
        $form   = $event->getForm();
        $organizationFieldName = $this->getOrganizationFieldName($entity);
        if (!$organizationFieldName) {
            return;
        }
        $propertyAccessor = $this->getPropertyAccessor();

        if ($entity->getId() ) {
            $organization = $propertyAccessor->getValue($entity, $organizationFieldName);
        } else {
            $organization = $this->getOrganizationFromRequest();
            $propertyAccessor->setValue($entity, $organizationFieldName, $organization);
        }

       //add a read-only organization field, user not able to edit.
       $form->add($organizationFieldName, TextType::class, [
            'disabled' => true,
            'data' =>  $organization ? $organization->getName() : '',
            'mapped' => false,
            'required' => false,
            'label' => "pintushi.organization.organization.label"
        ]);
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


    protected function getOrganizationFromRequest()
    {
        $organizationId = $this->requestStack->getMasterRequest()->query->get(self::QUERY_ID);
        if ($organizationId && $organization = $this->organizationRepository->find($organizationId)) {
            return $organization;
        }

        return  null;
    }

    /**
     * @param object $entity
     */
    protected function updateOrganization($entity)
    {
        $organizationField = $this->getOrganizationFieldName($entity);
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

    protected function getOrganizationFieldName($entity)
    {
        if (!is_object($entity)) {
            return null;
        }

        return $this->ownershipMetadataProvider->getMetadata(ClassUtils::getClass($entity))->getOrganizationFieldName();
    }
}
