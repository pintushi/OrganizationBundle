<?php

namespace Pintushi\Bundle\OrganizationBundle\Form\Type;

use Pintushi\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Pintushi\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Pintushi\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form for Business unit entity
 */
class BusinessUnitType extends AbstractType
{
    const FORM_NAME = 'oro_business_unit';

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param BusinessUnitManager    $businessUnitManager
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        BusinessUnitManager $businessUnitManager,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->businessUnitManager = $businessUnitManager;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label'    => 'oro.organization.businessunit.name.label',
                    'required' => true,
                ]
            )
            ->add(
                'parentBusinessUnit',
                HiddenType::class,
                [
                    'required' => false,
                    'label' => 'oro.organization.businessunit.parent.label',
                ]
            )
        ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * Change the autocomplete handler to "parent-business-units" for parentBusinessUnit field in case of
     * edit Business Unit page. The "parent-business-units" handler returns a list of Business units excluding
     * the given Business unit and its children.
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $entity = $event->getData();

        if (is_object($entity) && $entity->getId()) {
            $form->remove('parentBusinessUnit');
            $form->add(
                'parentBusinessUnit',
                BusinessUnitSelectAutocomplete::class,
                [
                    'required' => false,
                    'label' => 'oro.organization.businessunit.parent.label',
                    'autocomplete_alias' => 'parent-business-units',
                    'placeholder' => 'oro.business_unit.form.none_business_user',
                    'configs' => [
                        'multiple' => false,
                        'component'   => 'parent-business-units-autocomplete',
                        'width'       => '400px',
                        'placeholder' => 'oro.dashboard.form.choose_business_unit',
                        'allowClear'  => true,
                        'entity_id' => $entity->getId()
                    ]
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'              => 'Pintushi\Bundle\OrganizationBundle\Entity\BusinessUnit',
                'ownership_disabled'      => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::FORM_NAME;
    }
}
