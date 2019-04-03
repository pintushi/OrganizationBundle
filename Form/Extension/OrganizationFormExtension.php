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
use Pintushi\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Pintushi\Bundle\OrganizationBundle\Entity\Organization;
use Pintushi\Bundle\OrganizationBundle\Form\EventListener\OwnerFormSubscriber;
use Videni\Bundle\RestBundle\Form\DataTransformer\EntityToIdTransformer;
use Pintushi\Bundle\OrganizationBundle\Form\Type\OrganizationSelectType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pintushi\Bundle\UserBundle\Entity\User;

class OrganizationFormExtension extends AbstractTypeExtension
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    protected $ownershipMetadataProvider;

    protected $doctrineHelper;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    private $currentUser;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        DoctrineHelper $doctrineHelper,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
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

        $user = $this->getCurrentUser();
        if (!$user) {
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

        $organization = $propertyAccessor->getValue($entity, $organizationFieldName);

        if (null === $organization) {
            $organization = $this->getCurrentUser()->getOrganization();
            $propertyAccessor->setValue($entity, $organizationFieldName, $organization);
        }

       //add a read-only organization field, user not able to edit.
       $form->add($organizationFieldName, TextType::class, [
            'disabled' => true,
            'data' => $organization->getName(),
            'mapped' => false,
            'required' => false,
            'label' => "pintushi.organization.organization.label",
            'attr' => ['disabled']
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

    protected function getOrganizationFieldName($entity)
    {
        if (!is_object($entity)) {
            return null;
        }

        return $this->ownershipMetadataProvider->getMetadata(ClassUtils::getClass($entity))->getOrganizationFieldName();
    }
}
